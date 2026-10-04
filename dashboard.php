<?php
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/config/database.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: auth/login.php');
    exit;
}

function dashboard_escape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$userName = trim((string) ($_SESSION['user_name'] ?? 'there'));

$totalVehicles = 0;
$expiringSoon = 0;
$expiredDocuments = 0;
$validDocuments = 0;
$totalDocuments = 0;
$upcomingRenewals = [];
$vehicleRows = [];

try {
    // Count only vehicles owned by the currently logged-in user.
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    $totalVehicles = (int) $stmt->fetchColumn();

    // Dashboard document counts include current documents only; old renewal records are excluded.
    $stmt = $pdo->prepare("\n        SELECT\n            COUNT(*) AS total_documents,\n            COALESCE(SUM(CASE WHEN expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 28 DAY) THEN 1 ELSE 0 END), 0) AS expiring_soon,\n            COALESCE(SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END), 0) AS expired_documents,\n            COALESCE(SUM(CASE WHEN expiry_date > DATE_ADD(CURDATE(), INTERVAL 28 DAY) THEN 1 ELSE 0 END), 0) AS valid_documents\n        FROM documents\n        WHERE user_id = :user_id AND is_current = 1\n    ");
    $stmt->execute(['user_id' => $userId]);
    $documentStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $totalDocuments = (int) ($documentStats['total_documents'] ?? 0);
    $expiringSoon = (int) ($documentStats['expiring_soon'] ?? 0);
    $expiredDocuments = (int) ($documentStats['expired_documents'] ?? 0);
    $validDocuments = (int) ($documentStats['valid_documents'] ?? 0);

    // Show current documents expiring within the next 28 days, including today.
    $stmt = $pdo->prepare("\n        SELECT d.id, d.document_type, d.expiry_date,\n               v.registration_number, v.make, v.model\n        FROM documents d\n        LEFT JOIN vehicles v ON v.id = d.vehicle_id AND v.user_id = d.user_id\n        WHERE d.user_id = :user_id\n          AND d.is_current = 1\n          AND d.expiry_date >= CURDATE()\n          AND d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 28 DAY)\n        ORDER BY d.expiry_date ASC, d.id DESC\n        LIMIT 5\n    ");
    $stmt->execute(['user_id' => $userId]);
    $upcomingRenewals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Load the user's vehicles and count their current documents.
    $stmt = $pdo->prepare("\n        SELECT v.id, v.registration_number, v.vehicle_type, v.make, v.model, v.manufacturing_year,\n               COUNT(d.id) AS document_count,\n               COALESCE(SUM(CASE WHEN d.id IS NOT NULL AND d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 28 DAY) THEN 1 ELSE 0 END), 0) AS attention_count\n        FROM vehicles v\n        LEFT JOIN documents d\n          ON d.vehicle_id = v.id\n         AND d.user_id = v.user_id\n         AND d.is_current = 1\n        WHERE v.user_id = :user_id\n        GROUP BY v.id, v.registration_number, v.vehicle_type, v.make, v.model, v.manufacturing_year, v.created_at\n        ORDER BY v.created_at DESC, v.id DESC\n        LIMIT 5\n    ");
    $stmt->execute(['user_id' => $userId]);
    $vehicleRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('VehicleCare dashboard query failed: ' . $exception->getMessage());
    http_response_code(500);
    $dashboardError = 'We could not load your dashboard data. Please refresh the page or contact support if the problem continues.';
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="app-container">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/navbar.php'; ?>

        <div class="content-wrapper">
            <div class="welcome-section">
                <div>
                    <h1><?= dashboard_escape($greeting) ?>, <?= dashboard_escape($userName) ?></h1>
                    <p>Here's an overview of your vehicles and upcoming document renewals.</p>
                </div>
                <a href="vehicles/add.php" class="btn btn-primary add-vehicle-btn">
                    <i class="bi bi-plus-lg"></i>
                    Add Vehicle
                </a>
            </div>

            <?php if (!empty($dashboardError)): ?>
                <div class="alert alert-danger" role="alert"><?= dashboard_escape($dashboardError) ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-car-front-fill"></i></div>
                    <div class="stat-content"><span>Total Vehicles</span><h2><?= $totalVehicles ?></h2></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="bi bi-clock-history"></i></div>
                    <div class="stat-content"><span>Expiring Soon</span><h2><?= $expiringSoon ?></h2></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-exclamation-circle"></i></div>
                    <div class="stat-content"><span>Expired</span><h2><?= $expiredDocuments ?></h2></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                    <div class="stat-content"><span>Valid Documents</span><h2><?= $validDocuments ?></h2></div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card renewals-card">
                    <div class="card-header">
                        <div>
                            <h3>Upcoming Renewals</h3>
                            <p>Current documents expiring within 28 days</p>
                        </div>
                        <a href="documents/renewals.php" class="view-all">View all</a>
                    </div>

                    <div class="renewal-list">
                        <?php if (empty($upcomingRenewals)): ?>
                            <div class="empty-state">
                                <p>No upcoming renewals in the next 28 days.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcomingRenewals as $renewal): ?>
                                <?php
                                    $daysRemaining = (int) floor((strtotime($renewal['expiry_date']) - strtotime(date('Y-m-d'))) / 86400);
                                    $type = (string) $renewal['document_type'];
                                    $iconClass = 'license';
                                    $iconName = 'bi-file-earmark-check';
                                    if ($type === 'Vehicle Insurance') {
                                        $iconClass = 'insurance';
                                        $iconName = 'bi-shield-check';
                                    } elseif ($type === 'Emission Test Certificate') {
                                        $iconClass = 'emission';
                                        $iconName = 'bi-wind';
                                    } elseif ($type === 'Driving License') {
                                        $iconClass = 'license';
                                        $iconName = 'bi-person-vcard';
                                    }
                                    $vehicleName = trim(((string) ($renewal['make'] ?? '')) . ' ' . ((string) ($renewal['model'] ?? '')));
                                    $vehicleLabel = $vehicleName !== '' ? $vehicleName : ((string) ($renewal['registration_number'] ?? ''));
                                    if ($vehicleLabel === '') {
                                        $vehicleLabel = 'Personal document';
                                    } elseif (!empty($renewal['registration_number']) && $vehicleName !== '') {
                                        $vehicleLabel .= ' • ' . $renewal['registration_number'];
                                    }
                                ?>
                                <div class="renewal-item">
                                    <div class="document-icon <?= dashboard_escape($iconClass) ?>"><i class="bi <?= dashboard_escape($iconName) ?>"></i></div>
                                    <div class="renewal-info">
                                        <h4><?= dashboard_escape($type) ?></h4>
                                        <span><?= dashboard_escape($vehicleLabel) ?></span>
                                    </div>
                                    <div class="renewal-status <?= $daysRemaining <= 7 ? 'warning' : 'normal' ?>">
    <span class="renewal-days">
        <?= $daysRemaining === 0 ? 'Today' : $daysRemaining . ' day' . ($daysRemaining === 1 ? '' : 's') ?>
        <?= $daysRemaining === 0 ? 'expires' : 'remaining' ?>
    </span>
</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card status-card">
                    <div class="card-header">
                        <div><h3>Document Status</h3><p>Overview of your current documents</p></div>
                    </div>
                    <div class="status-content">
                        <div class="status-circle">
                            <div><strong><?= $totalDocuments ?></strong><span>Total</span></div>
                        </div>
                        <div class="status-legend">
                            <div class="legend-item">
                                <span class="legend-dot green"></span>
                                <div><strong><?= $validDocuments ?></strong><span>Valid</span></div>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot orange"></span>
                                <div><strong><?= $expiringSoon ?></strong><span>Expiring Soon</span></div>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot red"></span>
                                <div><strong><?= $expiredDocuments ?></strong><span>Expired</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card vehicles-card">
                <div class="card-header">
                    <div><h3>My Vehicles</h3><p>Your registered vehicles</p></div>
                    <a href="vehicles/index.php" class="view-all">View all</a>
                </div>
                <div class="vehicle-table">
                    <div class="vehicle-row vehicle-header">
                        <span>Vehicle</span><span>Registration</span><span>Documents</span><span>Status</span><span></span>
                    </div>
                    <?php if (empty($vehicleRows)): ?>
                        <div class="empty-state">
                            <p>You haven't added any vehicles yet.</p>
                            <a href="vehicles/add.php" class="btn btn-primary">Add your first vehicle</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($vehicleRows as $vehicle): ?>
                            <?php
                                $vehicleName = trim(((string) ($vehicle['make'] ?? '')) . ' ' . ((string) ($vehicle['model'] ?? '')));
                                if ($vehicleName === '') {
                                    $vehicleName = (string) $vehicle['vehicle_type'];
                                }
                                $vehicleSubline = [];
                                if (!empty($vehicle['manufacturing_year'])) {
                                    $vehicleSubline[] = (string) $vehicle['manufacturing_year'];
                                }
                                $vehicleSubline[] = (string) $vehicle['vehicle_type'];
                                $attentionCount = (int) $vehicle['attention_count'];
                            ?>
                            <div class="vehicle-row">
                                <div class="vehicle-name">
                                    <div class="vehicle-icon"><i class="bi bi-car-front-fill"></i></div>
                                    <div><strong><?= dashboard_escape($vehicleName) ?></strong><small><?= dashboard_escape(implode(' • ', $vehicleSubline)) ?></small></div>
                                </div>
                                <span><?= dashboard_escape($vehicle['registration_number']) ?></span>
                                <span><?= (int) $vehicle['document_count'] ?> <?= (int) $vehicle['document_count'] === 1 ? 'Document' : 'Documents' ?></span>
                                <span>
                                    <?php if ($attentionCount > 0): ?>
                                        <span class="status-badge warning-badge"><?= $attentionCount ?> Attention</span>
                                    <?php else: ?>
                                        <span class="status-badge success">All Active</span>
                                    <?php endif; ?>
                                </span>
                                <a href="vehicles/view.php?id=<?= (int) $vehicle['id'] ?>" class="row-action" aria-label="View <?= dashboard_escape($vehicleName) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
