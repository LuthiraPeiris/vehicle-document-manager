<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$email = '';
$error = '';
$success = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Your session has expired. Please refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $updateStmt = $pdo->prepare('UPDATE users SET reset_token = :token, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = :id');
                $updateStmt->execute(['token' => $token, 'id' => $user['id']]);

                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';
                $resetLink = $scheme . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/reset-password.php?token=' . $token;

                $mailConfig = [];
                $mailConfigPath = 'C:\\xampp\\private\\vehicle-document-manager-mail.php';
                if (is_file($mailConfigPath)) {
                    $mailConfig = require $mailConfigPath;
                }

                $smtpHost = getenv('SMTP_HOST') ?: ($mailConfig['host'] ?? '');
                $smtpPort = (int) (getenv('SMTP_PORT') ?: ($mailConfig['port'] ?? 587));
                $smtpUser = getenv('SMTP_USER') ?: ($mailConfig['username'] ?? '');
                $smtpPass = getenv('SMTP_PASSWORD') ?: ($mailConfig['password'] ?? '');
                $smtpEnc  = strtolower((string) (getenv('SMTP_ENCRYPTION') ?: ($mailConfig['encryption'] ?? 'tls')));
                $fromEmail = getenv('SMTP_FROM_EMAIL') ?: ($mailConfig['from_email'] ?? $smtpUser);
                $fromName  = getenv('SMTP_FROM_NAME') ?: ($mailConfig['from_name'] ?? 'VehicleCare');

                if ($smtpHost !== '' && $smtpUser !== '' && $smtpPass !== '') {
                    $mail = new PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host = $smtpHost;
                        $mail->SMTPAuth = true;
                        $mail->Username = $smtpUser;
                        $mail->Password = $smtpPass;
                        $mail->Port = $smtpPort;
                        $mail->CharSet = 'UTF-8';
                        $mail->Timeout = 20;

                        if ($smtpEnc === 'tls') {
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        } elseif ($smtpEnc === 'ssl') {
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        }

                        $mail->setFrom($fromEmail, $fromName);
                        $mail->addAddress($email);

                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset - VehicleCare';

                        $mail->Body = "
                            <div style=\"font-family:Arial,sans-serif;line-height:1.6;\">
                                <h2>Password Reset Request</h2>
                                <p>Hello,</p>
                                <p>We received a request to reset your password for VehicleCare. Click the link below to set a new password:</p>
                                <p><a href=\"{$resetLink}\">Reset Password</a></p>
                                <p>If you did not request a password reset, please ignore this email.</p>
                                <p>Regards,<br>VehicleCare Team</p>
                            </div>
                        ";

                        $mail->AltBody = "Hello,\n\nWe received a request to reset your password for VehicleCare. Please visit the following link to set a new password:\n\n{$resetLink}\n\nIf you did not request a password reset, please ignore this email.\n\nRegards,\nVehicleCare Team";

                        $mail->send();
                    } catch (Throwable $e) {
                        error_log('Failed to send password reset email: ' . $e->getMessage());
                    }
                }
                
                $success = 'Password reset instructions have been sent to your email address.';
            } else {
                // To prevent email enumeration, pretend it was successful.
                $success = 'If an account with that email exists, password reset instructions have been sent.';
            }
        } catch (PDOException $e) {
            error_log('Forgot password database error: ' . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}

$csrfToken = $_SESSION['csrf_token'];

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-back-wrapper">
        <a href="login.php" class="auth-back-btn">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Login</span>
        </a>
    </div>

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
                    <i class="bi bi-key"></i>
                </div>
                <h1>Forgot Password</h1>
                <p>Enter your email and we will send you a link to reset your password.</p>
            </div>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success" role="status">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($success === ''): ?>
            <form method="POST" action="forgot-password.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="auth-form-group">
                    <label for="email">Email Address</label>
                    <div class="auth-input-wrapper">
                        <i class="bi bi-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>

                <button type="submit" class="auth-submit-btn">
                    Send Reset Link
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>
