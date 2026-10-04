
<?php

require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/config/database.php';

// Generate a CSRF token for the profile form.
if (empty($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$successMessage = '';
$userId = (int) $_SESSION['user_id'];

// Load the logged-in user's profile.
$stmt = $pdo->prepare(
    'SELECT id, full_name, email, phone_number, google_id, created_at
     FROM users
     WHERE id = :user_id
     LIMIT 1'
);
$stmt->execute(['user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();

    header('Location: /vehicle-document-manager/auth/login.php');
    exit;
}

// Update the profile when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['profile_csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Your session token is invalid. Please refresh the page and try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        } elseif (mb_strlen($fullName) > 150) {
            $errors[] = 'Full name must not exceed 150 characters.';
        }

        if (mb_strlen($phoneNumber) > 30) {
            $errors[] = 'Phone number must not exceed 30 characters.';
        } elseif (
            $phoneNumber !== '' &&
            !preg_match('/^[0-9+\s().-]+$/', $phoneNumber)
        ) {
            $errors[] = 'Please enter a valid phone number.';
        }

        if (empty($errors)) {
            try {
                $update = $pdo->prepare(
                    'UPDATE users
                     SET full_name = :full_name,
                         phone_number = :phone_number
                     WHERE id = :user_id'
                );

                $update->execute([
                    'full_name' => $fullName,
                    'phone_number' => $phoneNumber !== ''
                        ? $phoneNumber
                        : null,
                    'user_id' => $userId,
                ]);

                // Keep the navbar and dashboard greeting up to date.
                $_SESSION['user_name'] = $fullName;

                // Reload the saved profile from the database.
                $stmt = $pdo->prepare(
                    'SELECT id, full_name, email, phone_number, google_id, created_at
                     FROM users
                     WHERE id = :user_id
                     LIMIT 1'
                );
                $stmt->execute(['user_id' => $userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
                $successMessage = 'Your profile has been updated successfully.';
            } catch (PDOException $e) {
                error_log('VehicleCare profile update failed: ' . $e->getMessage());
                $errors[] = 'Unable to update your profile right now. Please try again.';
            }
        }
    }
}

$pageTitle = 'My Profile';
$initials = '';

foreach (preg_split('/\s+/', trim($user['full_name'])) as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));

        if (mb_strlen($initials) >= 2) {
            break;
        }
    }
}

if ($initials === '') {
    $initials = 'U';
}

$joinedDate = !empty($user['created_at'])
    ? date('F j, Y', strtotime($user['created_at']))
    : 'Not available';

?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="app-container">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/includes/navbar.php'; ?>

        <div class="content-wrapper">

            <div class="welcome-section">
                <div>
                    <h1>My Profile</h1>
                    <p>Manage your personal information and account details.</p>
                </div>
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="profile-message profile-success" role="status">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="profile-message profile-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="profile-layout">

                <!-- Profile summary -->
                <section class="dashboard-card profile-summary">

                    <div class="profile-avatar">
                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <h2>
                        <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>
                    </h2>

                    <p class="profile-email">
                        <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <span class="profile-role">
                        <i class="bi bi-person-check"></i>
                        Vehicle Owner
                    </span>

                    <div class="profile-summary-divider"></div>

                    <div class="profile-summary-detail">
                        <i class="bi bi-calendar-check"></i>
                        <div>
                            <span>Member since</span>
                            <strong>
                                <?= htmlspecialchars($joinedDate, ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>
                    </div>

                    <div class="profile-summary-detail">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <span>Sign-in method</span>
                            <strong>
                                <?= !empty($user['google_id'])
                                    ? 'Google'
                                    : 'Email and password' ?>
                            </strong>
                        </div>
                    </div>

                </section>

                <!-- Editable profile form -->
                <section class="dashboard-card profile-form-card">

                    <div class="card-header">
                        <div>
                            <h3>Personal Information</h3>
                            <p>Update the information associated with your account.</p>
                        </div>
                    </div>

                    <form method="POST" action="profile.php" class="profile-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                $_SESSION['profile_csrf_token'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                        <div class="profile-field">
                            <label for="full_name">Full Name</label>

                            <div class="profile-input-wrapper">
                                <i class="bi bi-person"></i>
                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    value="<?= htmlspecialchars(
                                        $user['full_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    maxlength="150"
                                    autocomplete="name"
                                    required
                                >
                            </div>
                        </div>

                        <div class="profile-field">
                            <label for="email">Email Address</label>

                            <div class="profile-input-wrapper">
                                <i class="bi bi-envelope"></i>
                                <input
                                    type="email"
                                    id="email"
                                    value="<?= htmlspecialchars(
                                        $user['email'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    readonly
                                    aria-describedby="email-help"
                                >
                            </div>

                            <small id="email-help">
                                Your email address cannot be changed here.
                            </small>
                        </div>

                        <div class="profile-field">
                            <label for="phone_number">Phone Number</label>

                            <div class="profile-input-wrapper">
                                <i class="bi bi-telephone"></i>
                                <input
                                    type="tel"
                                    id="phone_number"
                                    name="phone_number"
                                    value="<?= htmlspecialchars(
                                        $user['phone_number'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    maxlength="30"
                                    autocomplete="tel"
                                    placeholder="Enter your phone number"
                                >
                            </div>

                            <small>
                                Add your phone number for future reminder features.
                            </small>
                        </div>

                        <div class="profile-form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle"></i>
                                Save Changes
                            </button>
                        </div>

                    </form>

                </section>

            </div>

        </div>

    </main>

</div>

<style>
    .profile-layout {
        display: grid;
        grid-template-columns: minmax(240px, 0.8fr) minmax(0, 1.7fr);
        gap: 24px;
        align-items: start;
    }

    .profile-summary {
        padding: 32px 24px;
        text-align: center;
    }

    .profile-avatar {
        width: 88px;
        height: 88px;
        margin: 0 auto 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 28px;
        font-weight: 700;
    }

    .profile-summary h2 {
        margin: 0 0 8px;
        font-size: 22px;
        overflow-wrap: anywhere;
    }

    .profile-email {
        margin: 0 0 16px;
        color: #64748b;
        overflow-wrap: anywhere;
    }

    .profile-role {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 12px;
        border-radius: 20px;
        background: #f0fdf4;
        color: #15803d;
        font-size: 13px;
        font-weight: 600;
    }

    .profile-summary-divider {
        height: 1px;
        margin: 26px 0;
        background: #e5e7eb;
    }

    .profile-summary-detail {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        text-align: left;
        margin-top: 20px;
    }

    .profile-summary-detail > i {
        color: #0284c7;
        font-size: 19px;
        margin-top: 2px;
    }

    .profile-summary-detail div {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .profile-summary-detail span {
        color: #64748b;
        font-size: 13px;
    }

    .profile-summary-detail strong {
        overflow-wrap: anywhere;
        font-size: 14px;
    }

    .profile-form-card {
        padding: 28px;
    }

    .profile-form-card .card-header {
        margin-bottom: 26px;
    }

    .profile-form {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    .profile-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .profile-field label {
        font-size: 14px;
        font-weight: 600;
        color: #334155;
    }

    .profile-input-wrapper {
        position: relative;
    }

    .profile-input-wrapper > i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        pointer-events: none;
    }

    .profile-input-wrapper input {
        width: 100%;
        min-height: 46px;
        padding: 11px 14px 11px 42px;
        border: 1px solid #dbe2ea;
        border-radius: 8px;
        background: #fff;
        color: #1e293b;
        font: inherit;
        box-sizing: border-box;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .profile-input-wrapper input:focus {
        outline: none;
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }

    .profile-input-wrapper input[readonly] {
        background: #f8fafc;
        color: #64748b;
        cursor: not-allowed;
    }

    .profile-field small {
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .profile-form-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 6px;
    }

    .profile-form-actions .btn {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .profile-message {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 14px 16px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-size: 14px;
    }

    .profile-success {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .profile-error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    @media (max-width: 900px) {
        .profile-layout {
            grid-template-columns: 1fr;
        }

        .profile-summary {
            padding: 24px;
        }
    }

    @media (max-width: 480px) {
        .profile-form-card {
            padding: 20px 16px;
        }

        .profile-form-actions .btn {
            width: 100%;
        }
    }
</style>

<script src="assets/js/app.js"></script>

</body>
</html>
