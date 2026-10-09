<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$configFile = __DIR__ . '/../config/google-oauth.php';
$config = is_file($configFile) ? require $configFile : [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: '',
];

if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect_uri'])) {
    error_log('Google OAuth callback is not configured. Missing credentials.');
    http_response_code(500);
    exit('Google sign-in is temporarily unavailable.');
}

// Handle Google cancellation or authorization errors.
if (isset($_GET['error'])) {
    header('Location: login.php?google_error=cancelled');
    exit;
}

// Validate OAuth state to prevent CSRF attacks.
$state = $_GET['state'] ?? '';
$expectedState = $_SESSION['google_oauth_state'] ?? '';

unset($_SESSION['google_oauth_state']);

if (
    !is_string($state) ||
    !is_string($expectedState) ||
    $expectedState === '' ||
    !hash_equals($expectedState, $state)
) {
    http_response_code(400);
    exit('Invalid Google sign-in request. Please try again.');
}

// Validate authorization code.
$code = $_GET['code'] ?? '';

if (!is_string($code) || $code === '') {
    header('Location: login.php?google_error=failed');
    exit;
}

if (!function_exists('curl_init')) {
    error_log('Google OAuth requires the PHP cURL extension.');

    http_response_code(500);
    exit('Google sign-in is temporarily unavailable.');
}

try {
    // Exchange the authorization code for an access token.
    $curl = curl_init('https://oauth2.googleapis.com/token');

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $tokenResponse = curl_exec($curl);
    $tokenStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $tokenError = curl_error($curl);

    curl_close($curl);

    if (
        $tokenResponse === false ||
        $tokenStatus !== 200
    ) {
        error_log('Google OAuth token exchange failed: ' . $tokenError);
        throw new RuntimeException('Google token exchange failed.');
    }

    $tokenData = json_decode($tokenResponse, true);

    if (
        !is_array($tokenData) ||
        empty($tokenData['access_token'])
    ) {
        throw new RuntimeException('Google access token was not returned.');
    }

    // Retrieve the authenticated Google user's profile.
    $curl = curl_init('https://openidconnect.googleapis.com/v1/userinfo');

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $tokenData['access_token'],
        ],
    ]);

    $profileResponse = curl_exec($curl);
    $profileStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $profileError = curl_error($curl);

    curl_close($curl);

    if (
        $profileResponse === false ||
        $profileStatus !== 200
    ) {
        error_log('Google userinfo request failed: ' . $profileError);
        throw new RuntimeException('Google profile request failed.');
    }

    $profile = json_decode($profileResponse, true);

    // Require a Google account ID and a verified email.
    if (
        !is_array($profile) ||
        empty($profile['sub']) ||
        empty($profile['email']) ||
        !filter_var($profile['email'], FILTER_VALIDATE_EMAIL) ||
        ($profile['email_verified'] ?? false) !== true
    ) {
        throw new RuntimeException('Google profile verification failed.');
    }

    $googleId = $profile['sub'];
    $email = strtolower(trim($profile['email']));
    $fullName = trim($profile['name'] ?? '');

    if ($fullName === '') {
        $fullName = $email;
    }

    // Look up an existing Google account first.
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email
         FROM users
         WHERE google_id = :google_id
         LIMIT 1'
    );

    $stmt->execute(['google_id' => $googleId]);
    $user = $stmt->fetch();

    if (!$user) {
        // Check whether this email already belongs to an account.
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, google_id
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $stmt->execute(['email' => $email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            // Never silently link Google to an existing account.
            // This avoids taking over an existing email/password account.
            if (
                empty($existingUser['google_id'])
            ) {
                header('Location: login.php?google_error=account_exists');
                exit;
            }

            // An existing Google ID that differs from this one
            // must not be overwritten.
            header('Location: login.php?google_error=account_exists');
            exit;
        }

        // Create a new Google-authenticated account.
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, google_id)
             VALUES (:full_name, :email, :google_id)'
        );

        $stmt->execute([
            'full_name' => mb_substr($fullName, 0, 150),
            'email' => $email,
            'google_id' => $googleId,
        ]);

        $userId = $pdo->lastInsertId();

        $user = [
            'id' => $userId,
            'full_name' => mb_substr($fullName, 0, 150),
            'email' => $email,
        ];
    }

    // Establish the same session used by normal login.
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];

    // Rotate the application's CSRF token after authentication.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: ../dashboard.php');
    exit;

} catch (Throwable $e) {
    error_log('Google OAuth callback error: ' . $e->getMessage());

    http_response_code(500);
    exit('Unable to sign in with Google right now. Please try again later.');
}
