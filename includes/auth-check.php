
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    if (isset($_COOKIE['remember_token'])) {
        require_once __DIR__ . '/../config/database.php';
        $stmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE remember_token = :token LIMIT 1');
        $stmt->execute(['token' => $_COOKIE['remember_token']]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
        } else {
            // Invalid token, clear it and redirect
            setcookie('remember_token', '', time() - 3600, '/');
            header('Location: /auth/login.php');
            exit;
        }
    } else {
        header('Location: /auth/login.php');
        exit;
    }
}