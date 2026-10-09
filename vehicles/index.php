<?php

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';

$vehicles = [];
$loadError = false;

try {
    $sql = "
        SELECT
            v.id,
            v.registration_number,
            v.vehicle_type,
            v.make,
            v.model,
            v.manufacturing_year,

            COUNT(d.id) AS total_documents,

            COALESCE(
                SUM(
                    CASE
                        WHEN d.id IS NOT NULL
                         AND d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS attention_documents,

            COALESCE(
                SUM(
                    CASE
                        WHEN d.id IS NOT NULL
                         AND d.expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS active_documents

        FROM vehicles v

        LEFT JOIN documents d
            ON d.vehicle_id = v.id
            AND d.user_id = v.user_id
            AND d.is_current = 1

        WHERE v.user_id = :user_id

        GROUP BY
            v.id,
            v.registration_number,
            v.vehicle_type,
            v.make,
            v.model,
            v.manufacturing_year,
            v.created_at

        ORDER BY v.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'user_id' => $_SESSION['user_id']
    ]);

    $vehicles = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('My Vehicles page error: ' . $e->getMessage());
    $loadError = true;
}

// Prepare status and searchable text for each vehicle.
foreach ($vehicles as &$vehicle) {
    $vehicle['total_documents'] = (int) $vehicle['total_documents'];
    $vehicle['attention_documents'] = (int) $vehicle['attention_documents'];
    $vehicle['active_documents'] = (int) $vehicle['active_documents'];

    if ($vehicle['attention_documents'] > 0) {
        $vehicle['status'] = 'attention';
    } elseif ($vehicle['total_documents'] > 0) {
        $vehicle['status'] = 'active';
    } else {
        $vehicle['status'] = 'none';
    }

    $vehicleName = trim(
        ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')
    );

    if ($vehicleName === '') {
        $vehicleName = $vehicle['vehicle_type'];
    }

    $vehicle['display_name'] = $vehicleName;

    $vehicle['search_text'] = strtolower(
        $vehicleName . ' ' .
        $vehicle['registration_number'] . ' ' .
        $vehicle['vehicle_type'] . ' ' .
        ($vehicle['manufacturing_year'] ?? '')
    );
}
unset($vehicle);

function vehicleEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

include __DIR__ . '/../includes/header.php';

?>

<div class="app-container">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../includes/navbar.php'; ?>

        <div class="content-wrapper">

            <!-- Page Header -->
            <div class="page-header">

                <div>
                    <h1>My Vehicles</h1>

                    <p>
                        Manage your registered vehicles and their documents.
                    </p>
                </div>

                <a href="add.php" class="btn btn-primary add-vehicle-btn">
                    <i class="bi bi-plus-lg"></i>
                    Add Vehicle
                </a>

            </div>

            <?php if ($loadError): ?>

                <div class="alert alert-danger" role="alert">
                    We couldn't load your vehicles right now.
                    Please refresh the page and try again.
                </div>

            <?php elseif (empty($vehicles)): ?>

                <!-- No vehicles registered yet -->
                <div class="empty-search" style="display: block;">

                    <div class="empty-icon">
                        <i class="bi bi-car-front"></i>
                    </div>

                    <h3>No vehicles added yet</h3>

                    <p>
                        Add your first vehicle to start managing its documents.
                    </p>

                    <a href="add.php" class="btn btn-primary add-vehicle-btn">
                        <i class="bi bi-plus-lg"></i>
                        Add Your First Vehicle
                    </a>

                </div>

            <?php else: ?>

                <!-- Search & Filter -->
                <div class="vehicle-toolbar">

                    <div class="search-box">

                        <i class="bi bi-search"></i>

                        <input
                            type="text"
                            id="vehicleSearch"
                            placeholder="Search by registration number or vehicle..."
                            autocomplete="off"
                        >

                    </div>

                    <select class="vehicle-filter" id="vehicleFilter">

                        <option value="all">All Vehicles</option>
                        <option value="attention">Needs Attention</option>
                        <option value="active">All Documents Active</option>

                    </select>

                </div>

                <!-- Database Vehicle Cards -->
                <div class="vehicles-grid" id="vehiclesGrid">

                    <?php foreach ($vehicles as $vehicle): ?>

                        <?php
                        $totalDocuments = $vehicle['total_documents'];
                        $attentionDocuments = $vehicle['attention_documents'];
                        $activeDocuments = $vehicle['active_documents'];
                        $status = $vehicle['status'];
                        ?>

                        <div
                            class="vehicle-card"
                            data-status="<?= vehicleEscape($status) ?>"
                            data-search="<?= vehicleEscape($vehicle['search_text']) ?>"
                        >

                            <div class="vehicle-card-top">

                                <div class="vehicle-main-info">

                                    <div class="vehicle-large-icon">
                                        <i class="bi bi-car-front-fill"></i>
                                    </div>

                                    <div>
                                        <h3>
                                            <?= vehicleEscape($vehicle['display_name']) ?>
                                        </h3>

                                        <span class="vehicle-registration">
                                            <?= vehicleEscape($vehicle['registration_number']) ?>
                                        </span>
                                    </div>

                                </div>

                                <button
                                    type="button"
                                    class="vehicle-menu-btn"
                                    aria-label="Vehicle options"
                                    disabled
                                >
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>

                            </div>

                            <div class="vehicle-details">

                                <div>
                                    <span>Vehicle Type</span>
                                    <strong>
                                        <?= vehicleEscape($vehicle['vehicle_type']) ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Year</span>
                                    <strong>
                                        <?= $vehicle['manufacturing_year']
                                            ? vehicleEscape($vehicle['manufacturing_year'])
                                            : 'Not provided' ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Documents</span>
                                    <strong><?= $totalDocuments ?></strong>
                                </div>

                            </div>

                            <div class="vehicle-status-row">

                                <?php if ($status === 'attention'): ?>

                                    <span class="status-badge warning-badge">
                                        <i class="bi bi-exclamation-circle"></i>
                                        Needs Attention
                                    </span>

                                    <span class="document-count">
                                        <?= $attentionDocuments ?>
                                        need attention
                                    </span>

                                <?php elseif ($status === 'active'): ?>

                                    <span class="status-badge success">
                                        <i class="bi bi-check-circle"></i>
                                        All Active
                                    </span>

                                    <span class="document-count">
                                        <?= $activeDocuments ?>
                                        / <?= $totalDocuments ?> active
                                    </span>

                                <?php else: ?>

                                    <span class="status-badge">
                                        <i class="bi bi-file-earmark"></i>
                                        No Documents
                                    </span>

                                    <span class="document-count">
                                        Add documents
                                    </span>

                                <?php endif; ?>

                            </div>

                            <div class="vehicle-card-actions">

                                <a
                                    href="view.php?id=<?= (int) $vehicle['id'] ?>"
                                    class="btn-view-vehicle"
                                >
                                    View Vehicle
                                    <i class="bi bi-arrow-right"></i>
                                </a>

                                <a
                                    href="add.php?id=<?= (int) $vehicle['id'] ?>"
                                    class="btn-edit-vehicle"
                                    aria-label="Edit vehicle"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <!-- Empty Search State -->
                <div class="empty-search" id="emptySearch" style="display: none;">

                    <div class="empty-icon">
                        <i class="bi bi-search"></i>
                    </div>

                    <h3>No vehicles found</h3>

                    <p>
                        Try a different search term or change the filter.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

<script src="../assets/js/app.js"></script>

<script>
const searchInput = document.getElementById("vehicleSearch");
const filterSelect = document.getElementById("vehicleFilter");
const vehicleCards = document.querySelectorAll(".vehicle-card[data-search]");
const emptySearch = document.getElementById("emptySearch");

function filterVehicles() {

    if (!searchInput || !filterSelect || !emptySearch) {
        return;
    }

    const searchTerm = searchInput.value.toLowerCase().trim();
    const filterValue = filterSelect.value;

    let visibleCount = 0;

    vehicleCards.forEach(card => {

        const searchData = card.dataset.search || "";
        const status = card.dataset.status || "";

        const matchesSearch = searchData.includes(searchTerm);

        const matchesFilter =
            filterValue === "all" ||
            filterValue === status;

        if (matchesSearch && matchesFilter) {
            card.style.display = "";
            visibleCount++;
        } else {
            card.style.display = "none";
        }

    });

    emptySearch.style.display =
        visibleCount === 0 ? "block" : "none";
}

if (searchInput && filterSelect && emptySearch) {
    searchInput.addEventListener("input", filterVehicles);
    filterSelect.addEventListener("change", filterVehicles);
}
</script>

</body>
</html>