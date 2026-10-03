<?php

require_once __DIR__ . '/page-context.php';

?>

<aside class="sidebar" id="sidebar">


    <!-- Brand -->
    <div class="brand">

        <div class="brand-icon">
            <i class="bi bi-car-front-fill"></i>
        </div>

        <span>VehicleCare</span>

    </div>


    <!-- Main Navigation -->
    <div class="sidebar-section">

        <span class="sidebar-label">
            MAIN
        </span>


        <!-- Dashboard -->
        <a
            href="/vehicle-document-manager/dashboard.php"
            class="sidebar-link <?= $currentSection === 'dashboard' ? 'active' : '' ?>"
        >

            <i class="bi bi-grid-1x2-fill"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- Vehicles -->
        <a
            href="/vehicle-document-manager/vehicles/"
            class="sidebar-link <?= $currentSection === 'vehicles' ? 'active' : '' ?>"
        >

            <i class="bi bi-car-front"></i>

            <span>
                My Vehicles
            </span>

        </a>


        <!-- Documents -->
        <a
            href="/vehicle-document-manager/documents/"
            class="sidebar-link <?= $currentSection === 'documents' ? 'active' : '' ?>"
        >

            <i class="bi bi-file-earmark-text"></i>

            <span>
                Documents
            </span>

        </a>


        <!-- Renewals -->
        <a
            href="/vehicle-document-manager/documents/renewals.php"
            class="sidebar-link <?= $currentSection === 'renewals' ? 'active' : '' ?>"
        >

            <i class="bi bi-arrow-repeat"></i>

            <span>
                Renewals
            </span>

        </a>

    </div>


    <!-- Bottom Navigation -->
    <div class="sidebar-bottom">


        <!-- Profile -->
        <a
            href="#"
            class="sidebar-link <?= $currentSection === 'profile' ? 'active' : '' ?>"
        >

            <i class="bi bi-person-circle"></i>

            <span>
                Profile
            </span>

        </a>


        <!-- Logout -->
        <a
            href="/vehicle-document-manager/auth/logout.php"
            class="sidebar-link logout-link"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>