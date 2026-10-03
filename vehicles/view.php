<?php
require_once __DIR__ . '/../includes/auth-check.php';
?>
<?php include '../includes/header.php'; ?>

<div class="app-container">

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include '../includes/navbar.php'; ?>

        <div class="content-wrapper">

            <!-- Page Header -->
            <div class="page-header">

                <div>
                    <div class="breadcrumb">
                        <a href="index.php">
                            My Vehicles
                        </a>

                        <i class="bi bi-chevron-right"></i>

                        <span>ABC-123</span>
                    </div>

                    <h1> Toyota Corolla</h1>

                    <p>
                        Vehicle registration: ABC-123
                    </p>
                </div>

                <div class="vehicle-detail-actions">

                    <a href="index.php" class="secondary-action">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>

                    <a href="add.php" class="add-vehicle-btn">
                        <i class="bi bi-pencil"></i>
                        Edit Vehicle
                    </a>

                </div>

            </div>


            <!-- Vehicle Summary -->
            <div class="vehicle-summary-card">

                <div class="vehicle-summary-main">

                    <div class="vehicle-summary-icon">
                        <i class="bi bi-car-front-fill"></i>
                    </div>

                    <div>
                        <h2>Toyota Corolla</h2>

                        <div class="vehicle-meta">

                            <span>
                                <i class="bi bi-credit-card-2-front"></i>
                                ABC-123
                            </span>

                            <span>
                                <i class="bi bi-car-front"></i>
                                Car
                            </span>

                            <span>
                                <i class="bi bi-calendar3"></i>
                                2020
                            </span>

                        </div>
                    </div>

                </div>


                <div class="vehicle-health">

                    <span class="health-label">
                        Document Status
                    </span>

                    <span class="status-badge success">
                        <i class="bi bi-check-circle"></i>
                        All Documents Active
                    </span>

                </div>

            </div>


            <!-- Documents Section -->
            <div class="section-heading">

                <div>
                    <h2>Vehicle Documents</h2>

                    <p>
                        Manage the documents and expiry dates associated
                        with this vehicle.
                    </p>
                </div>

                <a href="../documents/add.php" class="secondary-action">
                    <i class="bi bi-plus-lg"></i>
                    Add Document
                </a>

            </div>


            <!-- Document Cards -->
            <div class="document-grid">


                <!-- Driving License -->
                <div class="document-card">

                    <div class="document-card-header">

                        <div class="document-type-icon blue-icon">
                            <i class="bi bi-person-vcard"></i>
                        </div>

                        <div class="document-card-title">
                            <h3>Driving License</h3>

                            <span>
                                Driver documentation
                            </span>
                        </div>

                        <button class="document-menu-btn">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                    </div>


                    <div class="document-date">

                        <span>Expiry Date</span>

                        <strong>15 Nov 2026</strong>

                    </div>


                    <div class="document-status-line">

                        <span class="status-badge success">
                            <i class="bi bi-check-circle"></i>
                            Valid
                        </span>

                        <span class="remaining-text">
                            45 days remaining
                        </span>

                    </div>


                    <div class="document-card-footer">

                        <a href="#" class="document-action">
                            <i class="bi bi-pencil"></i>
                            Edit
                        </a>

                        <a href="#" class="document-action">
                            View Details
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>


                <!-- Revenue License -->
                <div class="document-card">

                    <div class="document-card-header">

                        <div class="document-type-icon orange-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>

                        <div class="document-card-title">
                            <h3>Revenue License</h3>

                            <span>
                                Vehicle licensing
                            </span>
                        </div>

                        <button class="document-menu-btn">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                    </div>


                    <div class="document-date">

                        <span>Expiry Date</span>

                        <strong>07 Oct 2026</strong>

                    </div>


                    <div class="document-status-line">

                        <span class="status-badge warning-badge">
                            <i class="bi bi-exclamation-circle"></i>
                            Expiring Soon
                        </span>

                        <span class="remaining-text warning-text">
                            7 days remaining
                        </span>

                    </div>


                    <div class="document-card-footer">

                        <a href="#" class="document-action">
                            <i class="bi bi-pencil"></i>
                            Edit
                        </a>

                        <a href="#" class="document-action">
                            View Details
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>


                <!-- Vehicle Insurance -->
                <div class="document-card">

                    <div class="document-card-header">

                        <div class="document-type-icon purple-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>

                        <div class="document-card-title">
                            <h3>Vehicle Insurance</h3>

                            <span>
                                Insurance coverage
                            </span>
                        </div>

                        <button class="document-menu-btn">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                    </div>


                    <div class="document-date">

                        <span>Expiry Date</span>

                        <strong>25 Dec 2026</strong>

                    </div>


                    <div class="document-status-line">

                        <span class="status-badge success">
                            <i class="bi bi-check-circle"></i>
                            Valid
                        </span>

                        <span class="remaining-text">
                            85 days remaining
                        </span>

                    </div>


                    <div class="document-card-footer">

                        <a href="#" class="document-action">
                            <i class="bi bi-pencil"></i>
                            Edit
                        </a>

                        <a href="#" class="document-action">
                            View Details
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>


                <!-- Emission Test -->
                <div class="document-card">

                    <div class="document-card-header">

                        <div class="document-type-icon green-icon">
                            <i class="bi bi-wind"></i>
                        </div>

                        <div class="document-card-title">
                            <h3>Emission Test Certificate</h3>

                            <span>
                                Emission compliance
                            </span>
                        </div>

                        <button class="document-menu-btn">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                    </div>


                    <div class="document-date">

                        <span>Expiry Date</span>

                        <strong>01 Oct 2026</strong>

                    </div>


                    <div class="document-status-line">

                        <span class="status-badge danger-badge">
                            <i class="bi bi-x-circle"></i>
                            Expired
                        </span>

                        <span class="remaining-text danger-text">
                            Expired 1 day ago
                        </span>

                    </div>


                    <div class="document-card-footer">

                        <a href="#" class="document-action">
                            <i class="bi bi-pencil"></i>
                            Edit
                        </a>

                        <a href="#" class="document-action">
                            View Details
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

<script src="../assets/js/app.js"></script>

</body>
</html>