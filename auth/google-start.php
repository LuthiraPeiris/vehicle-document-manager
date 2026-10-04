<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require __DIR__ . '/../config/google-oauth.php';

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