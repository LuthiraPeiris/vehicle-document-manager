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
                    <h1>Renewals</h1>

                    <p>
                        Keep track of documents that need to be renewed.
                    </p>
                </div>

                <div class="renewal-summary-badge">
                    <i class="bi bi-bell"></i>
                    3 documents need attention
                </div>

            </div>


            <!-- Renewal Overview -->
            <div class="renewal-overview-grid">

                <div class="renewal-overview-card urgent">

                    <div class="renewal-overview-icon">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>

                    <div>
                        <span>Urgent</span>
                        <strong>1</strong>
                        <small>Within 7 days</small>
                    </div>

                </div>


                <div class="renewal-overview-card upcoming">

                    <div class="renewal-overview-icon">
                        <i class="bi bi-clock"></i>
                    </div>

                    <div>
                        <span>Upcoming</span>
                        <strong>2</strong>
                        <small>Within 30 days</small>
                    </div>

                </div>


                <div class="renewal-overview-card expired">

                    <div class="renewal-overview-icon">
                        <i class="bi bi-x-circle"></i>
                    </div>

                    <div>
                        <span>Expired</span>
                        <strong>1</strong>
                        <small>Requires action</small>
                    </div>

                </div>

            </div>


            <!-- Renewal Timeline -->
            <div class="renewals-section">

                <div class="section-heading">

                    <div>
                        <h2>Documents Requiring Attention</h2>

                        <p>
                            Your upcoming and expired vehicle documents.
                        </p>
                    </div>

                    <select class="renewal-filter" id="renewalFilter">

                        <option value="all">
                            All
                        </option>

                        <option value="urgent">
                            Within 7 Days
                        </option>

                        <option value="upcoming">
                            Within 30 Days
                        </option>

                        <option value="expired">
                            Expired
                        </option>

                    </select>

                </div>


                <div class="renewal-list-card" id="renewalList">


                    <!-- Expired -->
                    <div
                        class="renewal-page-item"
                        data-category="expired"
                    >

                        <div class="renewal-date-column danger">

                            <strong>01</strong>
                            <span>OCT</span>

                        </div>


                        <div class="renewal-timeline-line">

                            <span class="timeline-dot danger"></span>

                        </div>


                        <div class="renewal-page-content">

                            <div class="renewal-page-main">

                                <div class="renewal-page-icon danger-icon">
                                    <i class="bi bi-wind"></i>
                                </div>

                                <div>

                                    <h3>
                                        Emission Test Certificate
                                    </h3>

                                    <p>
                                        Honda Vezel
                                        <span>•</span>
                                        XYZ-456
                                    </p>

                                </div>

                            </div>


                            <div class="renewal-page-status">

                                <span class="status-badge danger-badge">
                                    <i class="bi bi-x-circle"></i>
                                    Expired
                                </span>

                                <strong class="danger-text">
                                    Expired 1 day ago
                                </strong>

                            </div>


                            <div class="renewal-page-actions">

                                <button class="renewal-action-btn">
                                    <i class="bi bi-pencil"></i>
                                    Update
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- Urgent -->
                    <div
                        class="renewal-page-item"
                        data-category="urgent"
                    >

                        <div class="renewal-date-column warning">

                            <strong>07</strong>
                            <span>OCT</span>

                        </div>


                        <div class="renewal-timeline-line">

                            <span class="timeline-dot warning"></span>

                        </div>


                        <div class="renewal-page-content">

                            <div class="renewal-page-main">

                                <div class="renewal-page-icon warning-icon">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>

                                <div>

                                    <h3>
                                        Revenue License
                                    </h3>

                                    <p>
                                        Toyota Corolla
                                        <span>•</span>
                                        ABC-123
                                    </p>

                                </div>

                            </div>


                            <div class="renewal-page-status">

                                <span class="status-badge warning-badge">
                                    <i class="bi bi-clock"></i>
                                    Expiring Soon
                                </span>

                                <strong class="warning-text">
                                    7 days remaining
                                </strong>

                            </div>


                            <div class="renewal-page-actions">

                                <button class="renewal-action-btn">
                                    <i class="bi bi-pencil"></i>
                                    Update
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- Upcoming -->
                    <div
                        class="renewal-page-item"
                        data-category="upcoming"
                    >

                        <div class="renewal-date-column normal">

                            <strong>15</strong>
                            <span>NOV</span>

                        </div>


                        <div class="renewal-timeline-line">

                            <span class="timeline-dot normal"></span>

                        </div>


                        <div class="renewal-page-content">

                            <div class="renewal-page-main">

                                <div class="renewal-page-icon blue-icon">
                                    <i class="bi bi-person-vcard"></i>
                                </div>

                                <div>

                                    <h3>
                                        Driving License
                                    </h3>

                                    <p>
                                        Toyota Corolla
                                        <span>•</span>
                                        ABC-123
                                    </p>

                                </div>

                            </div>


                            <div class="renewal-page-status">

                                <span class="status-badge success">
                                    <i class="bi bi-check-circle"></i>
                                    Upcoming
                                </span>

                                <strong>
                                    45 days remaining
                                </strong>

                            </div>


                            <div class="renewal-page-actions">

                                <button class="renewal-action-btn">
                                    <i class="bi bi-pencil"></i>
                                    Update
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- Upcoming -->
                    <div
                        class="renewal-page-item"
                        data-category="upcoming"
                    >

                        <div class="renewal-date-column normal">

                            <strong>25</strong>
                            <span>DEC</span>

                        </div>


                        <div class="renewal-timeline-line">

                            <span class="timeline-dot normal"></span>

                        </div>


                        <div class="renewal-page-content">

                            <div class="renewal-page-main">

                                <div class="renewal-page-icon purple-icon">
                                    <i class="bi bi-shield-check"></i>
                                </div>

                                <div>

                                    <h3>
                                        Vehicle Insurance
                                    </h3>

                                    <p>
                                        Toyota Corolla
                                        <span>•</span>
                                        ABC-123
                                    </p>

                                </div>

                            </div>


                            <div class="renewal-page-status">

                                <span class="status-badge success">
                                    <i class="bi bi-check-circle"></i>
                                    Upcoming
                                </span>

                                <strong>
                                    85 days remaining
                                </strong>

                            </div>


                            <div class="renewal-page-actions">

                                <button class="renewal-action-btn">
                                    <i class="bi bi-pencil"></i>
                                    Update
                                </button>

                            </div>

                        </div>

                    </div>


                </div>


                <!-- Empty state -->
                <div
                    class="renewals-empty-state"
                    id="renewalsEmptyState"
                >

                    <div class="empty-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <h3>No renewals found</h3>

                    <p>
                        There are no documents matching this filter.
                    </p>

                </div>

            </div>


            <!-- Reminder Information -->
            <div class="reminder-info-card">

                <div class="reminder-info-icon">
                    <i class="bi bi-envelope"></i>
                </div>

                <div>

                    <h3>Email Reminders</h3>

                    <p>
                        The system will notify you by email when a
                        document approaches its expiry date.
                    </p>

                </div>

                <span class="reminder-status">
                    <i class="bi bi-check-circle"></i>
                    Enabled
                </span>

            </div>

        </div>

    </main>

</div>


<script src="../assets/js/app.js"></script>

<script>

const renewalFilter =
    document.getElementById("renewalFilter");

const renewalItems =
    document.querySelectorAll(".renewal-page-item");

const renewalsEmptyState =
    document.getElementById("renewalsEmptyState");


renewalFilter.addEventListener("change", function () {

    const selected =
        this.value;

    let visibleCount = 0;


    renewalItems.forEach(item => {

        const category =
            item.dataset.category;

        if (
            selected === "all" ||
            category === selected
        ) {

            item.style.display = "";

            visibleCount++;

        } else {

            item.style.display = "none";

        }

    });


    renewalsEmptyState.style.display =
        visibleCount === 0 ? "block" : "none";

});

</script>


</body>
</html>