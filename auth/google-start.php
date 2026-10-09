<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$configFile = __DIR__ . '/../config/google-oauth.php';
$config = is_file($configFile) ? require $configFile : [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: '',
];

if (empty($config['client_id']) || empty($config['redirect_uri'])) {
    error_log('Google OAuth is not configured. Missing client_id or redirect_uri.');
    http_response_code(500);
    exit('Google sign-in is temporarily unavailable.');
}

// Generate and store a CSRF protection state value.
$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;

$params = [
    'client_id' => $config['client_id'],
    'redirect_uri' => $config['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
];

$googleUrl = 'https://accounts.google.com/o/oauth2/v2/auth?'
    . http_build_query($params);

header('Location: ' . $googleUrl);
exit;