<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid or missing reset token.';
} else {
    // Validate token
    $stmt = $pdo->prepare('SELECT id FROM users WHERE reset_token = :token AND reset_token_expires_at > NOW() LIMIT 1');
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'This password reset link is invalid or has expired.';
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Your session has expired. Please refresh the page and try again.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $updateStmt = $pdo->prepare('UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id');
            $updateStmt->execute(['hash' => $hashedPassword, 'id' => $user['id']]);

            $success = 'Your password has been successfully reset. You can now login with your new password.';
        } catch (PDOException $e) {
            error_log('Reset password database error: ' . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}

$csrfToken = $_SESSION['csrf_token'];

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-brand">
        <a href="../index.php" class="public-brand">
            <span class="public-brand-icon">
                <i class="bi bi-car-front-fill"></i>
            </span>
            <span>VehicleCare</span>
        </a>
    </div>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h1>Reset Password</h1>
                <p>Create a new strong password for your account.</p>
            </div>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success" role="status">
                    <?= $success ?>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="login.php" class="auth-submit-btn">Return to Login</a>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($success === '' && empty($error)): ?>
            <form method="POST" action="reset-password.php?token=<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="auth-form-group">
                    <label for="password">New Password</label>
                    <div class="auth-input-wrapper">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <div class="auth-input-wrapper">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirm your new password" required>
                    </div>
                </div>

                <button type="submit" class="auth-submit-btn">
                    Reset Password
                    <i class="bi bi-check-circle"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>
