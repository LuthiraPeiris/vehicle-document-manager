<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove all session data.
$_SESSION = [];

// Remove the session cookie if cookies are enabled.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

if (isset($_COOKIE['remember_token'])) {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare('UPDATE users SET remember_token = NULL WHERE remember_token = :token');
    $stmt->execute(['token' => $_COOKIE['remember_token']]);
    setcookie('remember_token', '', time() - 3600, '/');
}
// Destroy the session.
session_destroy();

// Redirect to the login page.
header('Location: login.php');
exit;