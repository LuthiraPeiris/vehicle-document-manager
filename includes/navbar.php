<?php

require_once __DIR__ . '/page-context.php';

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
            <?= htmlspecialchars($pageTitle) ?>
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
                LU
            </div>


            <div class="user-info">

                <span class="user-name">
                    Luthira
                </span>

                <span class="user-role">
                    Vehicle Owner
                </span>

            </div>


            <i class="bi bi-chevron-down"></i>

        </div>

    </div>

</nav>