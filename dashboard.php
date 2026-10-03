<?php
require_once __DIR__ . '/includes/auth-check.php';
?>
<?php include 'includes/header.php'; ?>

<div class="app-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/navbar.php'; ?>

        <div class="content-wrapper">

            <!-- Welcome Section -->
            <div class="welcome-section">

                <div>
                    <h1>Good morning, Luthira 👋</h1>

                    <p>
                        Here's an overview of your vehicles and
                        upcoming document renewals.
                    </p>
                </div>

                <a href="vehicles/add.php" class="btn btn-primary add-vehicle-btn">
                    <i class="bi bi-plus-lg"></i>
                    Add Vehicle
                </a>

            </div>


            <!-- Statistics -->
            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon blue">
                        <i class="bi bi-car-front-fill"></i>
                    </div>

                    <div class="stat-content">

                        <span>Total Vehicles</span>

                        <h2>2</h2>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        <i class="bi bi-clock-history"></i>
                    </div>

                    <div class="stat-content">

                        <span>Expiring Soon</span>

                        <h2>3</h2>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon red">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>

                    <div class="stat-content">

                        <span>Expired</span>

                        <h2>1</h2>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <div class="stat-content">

                        <span>Valid Documents</span>

                        <h2>5</h2>

                    </div>

                </div>

            </div>


            <!-- Main Dashboard Grid -->
            <div class="dashboard-grid">


                <!-- Upcoming Renewals -->
                <div class="dashboard-card renewals-card">

                    <div class="card-header">

                        <div>
                            <h3>Upcoming Renewals</h3>

                            <p>
                                Documents that need your attention
                            </p>
                        </div>

                        <a href="#" class="view-all">
                            View all
                        </a>

                    </div>


                    <div class="renewal-list">

                        <!-- Renewal 1 -->
                        <div class="renewal-item">

                            <div class="document-icon insurance">
                                <i class="bi bi-shield-check"></i>
                            </div>

                            <div class="renewal-info">

                                <h4>Vehicle Insurance</h4>

                                <span>
                                    Toyota Corolla • ABC-123
                                </span>

                            </div>

                            <div class="renewal-status warning">
                                <strong>7 days</strong>
                                <span>remaining</span>
                            </div>

                        </div>


                        <!-- Renewal 2 -->
                        <div class="renewal-item">

                            <div class="document-icon license">
                                <i class="bi bi-file-earmark-check"></i>
                            </div>

                            <div class="renewal-info">

                                <h4>Revenue License</h4>

                                <span>
                                    Toyota Corolla • ABC-123
                                </span>

                            </div>

                            <div class="renewal-status warning">
                                <strong>15 days</strong>
                                <span>remaining</span>
                            </div>

                        </div>


                        <!-- Renewal 3 -->
                        <div class="renewal-item">

                            <div class="document-icon emission">
                                <i class="bi bi-wind"></i>
                            </div>

                            <div class="renewal-info">

                                <h4>Emission Test Certificate</h4>

                                <span>
                                    Honda Vezel • XYZ-456
                                </span>

                            </div>

                            <div class="renewal-status normal">
                                <strong>30 days</strong>
                                <span>remaining</span>
                            </div>

                        </div>

                    </div>

                </div>


                <!-- Document Status -->
                <div class="dashboard-card status-card">

                    <div class="card-header">

                        <div>
                            <h3>Document Status</h3>

                            <p>
                                Overview of all documents
                            </p>
                        </div>

                    </div>


                    <div class="status-content">

                        <div class="status-circle">
                            <div>
                                <strong>8</strong>
                                <span>Total</span>
                            </div>
                        </div>


                        <div class="status-legend">

                            <div class="legend-item">

                                <span class="legend-dot green"></span>

                                <div>
                                    <strong>5</strong>
                                    <span>Valid</span>
                                </div>

                            </div>


                            <div class="legend-item">

                                <span class="legend-dot orange"></span>

                                <div>
                                    <strong>2</strong>
                                    <span>Expiring Soon</span>
                                </div>

                            </div>


                            <div class="legend-item">

                                <span class="legend-dot red"></span>

                                <div>
                                    <strong>1</strong>
                                    <span>Expired</span>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- My Vehicles -->
            <div class="dashboard-card vehicles-card">

                <div class="card-header">

                    <div>
                        <h3>My Vehicles</h3>

                        <p>
                            Your registered vehicles
                        </p>
                    </div>

                    <a href="vehicles/index.php" class="view-all">
                        View all
                    </a>

                </div>


                <div class="vehicle-table">

                    <div class="vehicle-row vehicle-header">

                        <span>Vehicle</span>
                        <span>Registration</span>
                        <span>Documents</span>
                        <span>Status</span>
                        <span></span>

                    </div>


                    <div class="vehicle-row">

                        <div class="vehicle-name">

                            <div class="vehicle-icon">
                                <i class="bi bi-car-front-fill"></i>
                            </div>

                            <div>
                                <strong>Toyota Corolla</strong>
                                <small>2020 • Car</small>
                            </div>

                        </div>

                        <span>ABC-123</span>

                        <span>4 Documents</span>

                        <span>
                            <span class="status-badge success">
                                All Active
                            </span>
                        </span>

                        <a href="vehicles/view.php" class="row-action">
                            <i class="bi bi-chevron-right"></i>
                        </a>

                    </div>


                    <div class="vehicle-row">

                        <div class="vehicle-name">

                            <div class="vehicle-icon">
                                <i class="bi bi-car-front-fill"></i>
                            </div>

                            <div>
                                <strong>Honda Vezel</strong>
                                <small>2021 • SUV</small>
                            </div>

                        </div>

                        <span>XYZ-456</span>

                        <span>4 Documents</span>

                        <span>
                            <span class="status-badge warning-badge">
                                2 Attention
                            </span>
                        </span>

                        <a href="vehicles/view.php" class="row-action">
                            <i class="bi bi-chevron-right"></i>
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

<script src="assets/js/app.js"></script>

</body>
</html>