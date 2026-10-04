
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$email = '';
$errors = [];
$loginError = '';
$databaseError = false;

// Generate a CSRF token for the login form.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process the login form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate the CSRF token.
    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        $loginError = 'Your session has expired. Please refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginError = 'Please enter a valid email address.';
    } elseif (!is_string($password) || $password === '') {
        $loginError = 'Please enter your password.';
    } else {

        try {
            // Find the account associated with this email.
            $stmt = $pdo->prepare(
                'SELECT id, full_name, email, password_hash
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $stmt->execute(['email' => $email]);

            $user = $stmt->fetch();

            // Verify the password.
            if (
                $user &&
                !empty($user['password_hash']) &&
                password_verify($password, $user['password_hash'])
            ) {
                // Prevent session fixation.
                session_regenerate_id(true);

                // Store the authenticated user's information.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];

                // Rotate the CSRF token after successful login.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    $updateStmt = $pdo->prepare('UPDATE users SET remember_token = :token WHERE id = :id');
                    $updateStmt->execute(['token' => $token, 'id' => $user['id']]);
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }

                // Redirect to the dashboard.
                header('Location: ../dashboard.php');
                exit;
            }

            // Use the same message for unknown emails and wrong passwords.
            $loginError = 'Invalid email address or password. Please try again.';

        } catch (PDOException $e) {
            error_log('Login database error: ' . $e->getMessage());

            $databaseError = true;
            $loginError = 'Unable to sign in right now. Please try again later.';
        }
    }
}

$csrfToken = $_SESSION['csrf_token'];

include __DIR__ . '/../includes/header.php';

?>

<div class="auth-page">
    <div class="auth-back-wrapper">
    <a href="../index.php" class="auth-back-btn">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Home</span>
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
                    <i class="bi bi-person"></i>
                </div>

                <h1>Welcome back</h1>

                <p>
                    Sign in to manage your vehicles and documents.
                </p>

            </div>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success" role="status">
                    Account created successfully. You can now sign in.
                </div>
            <?php endif; ?>

            <?php
$googleError = $_GET['google_error'] ?? '';

if ($googleError === 'account_exists') {
    $loginError = 'An account with this email already exists. Please sign in with your password first.';
} elseif ($googleError === 'cancelled') {
    $loginError = 'Google sign-in was cancelled.';
} elseif ($googleError === 'failed') {
    $loginError = 'Google sign-in failed. Please try again.';
}
?>

            <?php if ($loginError !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="login.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="auth-form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="auth-input-wrapper">

                        <i class="bi bi-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="email"
                            maxlength="255"
                            required
                        >

                    </div>

                </div>

                <div class="auth-form-group">

                    <div class="auth-label-row">

                        <label for="password">
                            Password
                        </label>

                        <a href="forgot-password.php" id="forgotPasswordLink">
                            Forgot password?
                        </a>

                    </div>

                    <div class="auth-input-wrapper">

                        <i class="bi bi-lock"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >
                            <i class="bi bi-eye"></i>
                        </button>

                    </div>

                </div>

                <div class="auth-remember">

                    <label>

                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                        >

                        <span>Remember me</span>

                    </label>

                </div>

                <button
                    type="submit"
                    class="auth-submit-btn"
                >
                    Sign In
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>

        
            <div class="auth-divider">
                <span>Or continue with</span>
            </div>

            <a href="../auth/google-start.php" class="auth-create-account">
                <i class="bi bi-google"></i>
                Continue with Google
            </a>

            <div class="auth-divider">
                <span>New to VehicleCare?</span>
            </div>

            <a
                href="register.php"
                class="auth-create-account"
            >
                Create an account
            </a>

        </div>

        <p class="auth-footer-text">
            By continuing, you agree to use the VehicleCare system responsibly.
        </p>

    </div>

</div>

<script src="../assets/js/app.js"></script>

<script>
const passwordToggle = document.getElementById("passwordToggle");
const passwordInput = document.getElementById("password");

passwordToggle.addEventListener("click", function () {

    const isPassword = passwordInput.type === "password";

    passwordInput.type = isPassword ? "text" : "password";

    this.innerHTML = isPassword
        ? '<i class="bi bi-eye-slash"></i>'
        : '<i class="bi bi-eye"></i>';

    this.setAttribute(
        "aria-label",
        isPassword ? "Hide password" : "Show password"
    );
});


</script>

</body>
</html>