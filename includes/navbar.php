<?php

require_once __DIR__ . '/page-context.php';

// Get the logged-in user's name from the session.
$userName = trim($_SESSION['user_name'] ?? 'Vehicle Owner');

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

        <button
            class="notification-btn"
            type="button"
            aria-label="Notifications"
        >
            <i class="bi bi-bell"></i>
            <span class="notification-dot"></span>
        </button>

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
