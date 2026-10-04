<?php

require_once __DIR__ . '/page-context.php';

// Get the logged-in user's name from the session.
$userName = trim($_SESSION['user_name'] ?? 'Vehicle Owner');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$notificationCsrfToken = $_SESSION['csrf_token'];

// Generate initials from the user's name.
$nameParts = preg_split('/\s+/', $userName);
$initials = '';

foreach ($nameParts as $part) {
    if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    if (strlen($initials) >= 2) {
        break;
    }
}

// Use a fallback if no initials are available.
if ($initials === '') {
    $initials = 'U';
}

?>

<nav class="top-navbar">

    <div class="navbar-left">

        <button
            class="mobile-menu-btn"
            id="mobileMenuBtn"
            type="button"
        >
            <i class="bi bi-list"></i>
        </button>

        <span class="page-title">
            <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
        </span>

    </div>

    <div class="navbar-right">

        <div class="notification-menu" id="notificationMenu" data-csrf="<?= htmlspecialchars($notificationCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button
                class="notification-btn"
                id="notificationToggle"
                type="button"
                aria-label="Notifications"
                aria-expanded="false"
                aria-controls="notificationPanel"
            >
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="notification-badge" id="notificationBadge" hidden>0</span>
            </button>
            <section class="notification-panel" id="notificationPanel" aria-labelledby="notificationHeading" hidden>
                <header class="notification-panel-header">
                    <div>
                        <h2 id="notificationHeading">Notifications</h2>
                        <p>Updates about your documents</p>
                    </div>
                    <button class="notification-mark-all" id="markAllNotificationsRead" type="button">Mark all as read</button>
                </header>
                <p class="notification-feedback" id="notificationFeedback" role="status" hidden></p>
                <div class="notification-list" id="notificationList"></div>
                <div class="notification-empty" id="notificationEmpty" hidden>
                    <i class="bi bi-bell-slash" aria-hidden="true"></i>
                    <p>You’re all caught up.</p>
                </div>
            </section>
        </div>

        <div class="user-menu">

            <div class="user-avatar">
                <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="user-info">

                <span class="user-name">
                    <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                </span>

                <span class="user-role">
                    Vehicle Owner
                </span>

            </div>

            <i class="bi bi-chevron-down"></i>

        </div>

    </div>

</nav>
<script src="/vehicle-document-manager/assets/js/notifications.js" defer></script>
