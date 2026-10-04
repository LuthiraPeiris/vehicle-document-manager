<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Form values

$fullName = '';
$email = '';
$phoneNumber = '';
$errors = [];

// Process registration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF token

    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    // Validate full name
    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    } elseif (strlen($fullName) > 150) {
        $errors[] = 'Full name cannot exceed 150 characters.';
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (strlen($email) > 255) {
        $errors[] = 'Email address cannot exceed 255 characters.';
    }

    // Validate phone number
    if (strlen($phoneNumber) > 30) {
        $errors[] = 'Phone number cannot exceed 30 characters.';
    }

    // Validate password
    if (!is_string($password) || strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }

    if (!is_string($confirmPassword) || $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Create account
    if (empty($errors)) {
        try {
            // Check whether the email already exists
            $checkEmail = $pdo->prepare(
                'SELECT id FROM users WHERE email = :email LIMIT 1'
            );

            $checkEmail->execute([
                'email' => $email
            ]);

            if ($checkEmail->fetch()) {
                $errors[] = 'An account with this email already exists.';

            } else {
                // Hash the password before storing it
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert the user
                $insertUser = $pdo->prepare(
                    'INSERT INTO users
                        (full_name, email, phone_number, password_hash)
                     VALUES
                        (:full_name, :email, :phone_number, :password_hash)'
                );

                $insertUser->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone_number' => $phoneNumber !== ''
                        ? $phoneNumber
                        : null,
                    'password_hash' => $passwordHash
                ]);

                // Rotate the CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Redirect after successful registration
                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            // A concurrent registration may have used the same email
            if ($e->getCode() === '23000') {
                $errors[] = 'An account with this email already exists.';
            } else {
                $errors[] = 'Registration failed. Please try again later.';
            }
        }
    }
}

// Generate CSRF token for the form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
        <div class="auth-card register-card">
            <div class="auth-header">
                <div class="auth-icon">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h1>Create your account</h1>
                <p>
                    Start managing your vehicle documents in one place.
                </p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li>
                                <?= htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            <?php endif; ?>

            <form id="registerForm" method="POST" action="">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                <div class="auth-form-group">
                    <label for="name">
                        Full Name
                    </label>
                    <div class="auth-input-wrapper">
                        <i class="bi bi-person"></i>
                        <input
                            type="text"
                            id="name"
                            name="full_name"
                            value="<?= htmlspecialchars(
                                $fullName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Enter your full name"
                            maxlength="150"
                            autocomplete="name"
                            required
                        >
                    </div>
                </div>
                <div class="auth-form-group">
                    <label for="registerEmail">
                        Email Address
                    </label>
                    <div class="auth-input-wrapper">
                        <i class="bi bi-envelope"></i>
                        <input
                            type="email"
                            id="registerEmail"
                            name="email"
                            value="<?= htmlspecialchars(
                                $email,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="you@example.com"
                            maxlength="255"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>
                <div class="auth-form-group">
                    <label for="phone">
                        Phone Number
                    </label>
                    <div class="auth-input-wrapper">
                        <i class="bi bi-telephone"></i>
                        <input
                            type="tel"
                            id="phone"
                            name="phone_number"
                            value="<?= htmlspecialchars(
                                $phoneNumber,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Enter your phone number"
                            maxlength="30"
                            autocomplete="tel"
                        >
                    </div>
                </div>
                <div class="auth-form-row">
                    <div class="auth-form-group">
                        <label for="registerPassword">
                            Password
                        </label>
                        <div class="auth-input-wrapper">
                            <i class="bi bi-lock"></i>
                            <input
                                type="password"
                                id="registerPassword"
                                name="password"
                                placeholder="Create password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >
                        </div>
                    </div>
                    <div class="auth-form-group">
                        <label for="confirmPassword">
                            Confirm Password
                        </label>
                        <div class="auth-input-wrapper">
                            <i class="bi bi-lock"></i>
                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirm_password"
                                placeholder="Confirm password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >
                        </div>
                    </div>
                </div>
                <div class="password-requirement">
                    <i class="bi bi-info-circle"></i>
                    <span>
                        Use a strong password with a combination of
                        letters, numbers, and symbols.
                    </span>
                </div>
                <button
                    type="submit"
                    class="auth-submit-btn"
                >
                    Create Account
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>
            <div class="auth-divider">
                <span>Or sign up with</span>
            </div>

            <a href="google-start.php" class="auth-create-account">
                <i class="bi bi-google"></i>
                Continue with Google
            </a>
            <div class="auth-divider">
                <span>Already have an account?</span>
            </div>
            <a
                href="login.php"
                class="auth-create-account"
            >
                Sign in instead
            </a>
        </div>
        <p class="auth-footer-text">
            Your information will be securely stored in the VehicleCare system.
        </p>
    </div>
</div>
<script src="../assets/js/app.js"></script>
<script>

document.getElementById("registerForm").addEventListener("submit", function (event) {
    const password = document.getElementById("registerPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    if (password !== confirmPassword) {
        event.preventDefault();
        alert("Passwords do not match.");
    }
});
</script>
</body>
</html>