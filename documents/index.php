
<?php

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];

$today = new DateTimeImmutable('today');
$soonLimit = $today->modify('+30 days');

/*
|--------------------------------------------------------------------------
| Load current documents belonging to the logged-in user
|--------------------------------------------------------------------------
*/

$query = $pdo->prepare(
    'SELECT
        d.id,
        d.document_type,
        d.issue_date,
        d.expiry_date,
        d.vehicle_id,
        d.previous_document_id,
        d.is_current,
        v.registration_number,
        v.vehicle_type,
        v.make,
        v.model
     FROM documents d
     LEFT JOIN vehicles v
        ON v.id = d.vehicle_id
        AND v.user_id = d.user_id
     WHERE d.user_id = :user_id
       AND d.is_current = 1
     ORDER BY d.expiry_date ASC, d.id DESC'
);

$query->execute(['user_id' => $userId]);
$documents = $query->fetchAll();

/*
|--------------------------------------------------------------------------
| Calculate document statuses and summary statistics
|--------------------------------------------------------------------------
*/

$totalDocuments = count($documents);
$validCount = 0;
$expiringCount = 0;
$expiredCount = 0;

foreach ($documents as &$document) {
    $expiryDate = new DateTimeImmutable($document['expiry_date']);
    $daysRemaining = (int) $today->diff($expiryDate)->format('%r%a');

    if ($daysRemaining < 0) {
        $status = 'expired';
        $statusLabel = 'Expired';
        $statusClass = 'danger-badge';
        $statusIcon = 'bi-x-circle';
        $expiryMessage = 'Expired ' . abs($daysRemaining)
            . (abs($daysRemaining) === 1 ? ' day ago' : ' days ago');
        $expiryTextClass = 'danger-text';
        $expiredCount++;
    } elseif ($daysRemaining <= 30) {
        $status = 'expiring';
        $statusLabel = $daysRemaining === 0 ? 'Expires Today' : 'Expiring Soon';
        $statusClass = 'warning-badge';
        $statusIcon = 'bi-exclamation-circle';

        if ($daysRemaining === 0) {
            $expiryMessage = 'Expires today';
        } else {
            $expiryMessage = $daysRemaining
                . ($daysRemaining === 1 ? ' day remaining' : ' days remaining');
        }

        $expiryTextClass = 'warning-text';
        $expiringCount++;
    } else {
        $status = 'valid';
        $statusLabel = 'Valid';
        $statusClass = 'success';
        $statusIcon = 'bi-check-circle';
        $expiryMessage = $daysRemaining
            . ($daysRemaining === 1 ? ' day remaining' : ' days remaining');
        $expiryTextClass = '';
        $validCount++;
    }

    $document['days_remaining'] = $daysRemaining;
    $document['status'] = $status;
    $document['status_label'] = $statusLabel;
    $document['status_class'] = $statusClass;
    $document['status_icon'] = $statusIcon;
    $document['expiry_message'] = $expiryMessage;
    $document['expiry_text_class'] = $expiryTextClass;

    switch ($document['document_type']) {
        case 'Driving License':
            $document['type_key'] = 'driving-license';
            $document['icon'] = 'bi-person-vcard';
            $document['icon_class'] = 'blue';
            $document['description'] = 'Personal documentation';
            break;

        case 'Revenue License':
            $document['type_key'] = 'revenue-license';
            $document['icon'] = 'bi-file-earmark-text';
            $document['icon_class'] = 'orange';
            $document['description'] = 'Vehicle licensing';
            break;

        case 'Vehicle Insurance':
            $document['type_key'] = 'insurance';
            $document['icon'] = 'bi-shield-check';
            $document['icon_class'] = 'purple';
            $document['description'] = 'Insurance coverage';
            break;

        case 'Emission Test Certificate':
            $document['type_key'] = 'emission';
            $document['icon'] = 'bi-wind';
            $document['icon_class'] = 'green';
            $document['description'] = 'Emission compliance';
            break;

        default:
            $document['type_key'] = 'other';
            $document['icon'] = 'bi-file-earmark';
            $document['icon_class'] = 'blue';
            $document['description'] = 'Document';
    }

    $vehicleName = trim(
        ($document['make'] ?? '') . ' ' . ($document['model'] ?? '')
    );

    if ($vehicleName === '' && $document['vehicle_id'] !== null) {
        $vehicleName = ucfirst($document['vehicle_type'] ?? 'Vehicle');
    }

    $document['vehicle_name'] = $vehicleName;
}
unset($document);

?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="app-container">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../includes/navbar.php'; ?>

        <div class="content-wrapper">

            
            <div class="page-header">
                <div>
                    <h1>Documents</h1>
                    <p>View and manage all your current documents in one place.</p>
                </div>

                <a href="add.php" class="secondary-action">
                    <i class="bi bi-plus-lg"></i>
                    Add Document
                </a>
            </div>

            <?php if (isset($_GET['saved'])): ?>
                <div class="alert alert-success" role="alert">
                    Document saved successfully.
                </div>
            <?php endif; ?>

            <!-- Document Summary -->
            <div class="document-stats-grid">

                <div class="document-stat-card">
                    <div class="document-stat-icon blue">
                        <i class="bi bi-files"></i>
                    </div>
                    <div>
                        <span>Total Documents</span>
                        <strong><?= $totalDocuments ?></strong>
                    </div>
                </div>

                <div class="document-stat-card">
                    <div class="document-stat-icon green">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <span>Valid</span>
                        <strong><?= $validCount ?></strong>
                    </div>
                </div>

                <div class="document-stat-card">
                    <div class="document-stat-icon orange">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div>
                        <span>Expiring Soon</span>
                        <strong><?= $expiringCount ?></strong>
                    </div>
                </div>

                <div class="document-stat-card">
                    <div class="document-stat-icon red">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                    <div>
                        <span>Expired</span>
                        <strong><?= $expiredCount ?></strong>
                    </div>
                </div>

            </div>

            <!-- Search and Filters -->
            <div class="documents-toolbar">

                <div class="document-search-box">
                    <i class="bi bi-search"></i>
                    <input
                        type="text"
                        id="documentSearch"
                        placeholder="Search by document, vehicle or registration..."
                    >
                </div>

                <select id="documentTypeFilter" class="document-filter">
                    <option value="all">All Document Types</option>
                    <option value="driving-license">Driving License</option>
                    <option value="revenue-license">Revenue License</option>
                    <option value="insurance">Vehicle Insurance</option>
                    <option value="emission">Emission Test Certificate</option>
                </select>

                <select id="documentStatusFilter" class="document-filter">
                    <option value="all">All Status</option>
                    <option value="valid">Valid</option>
                    <option value="expiring">Expiring Soon</option>
                    <option value="expired">Expired</option>
                </select>

            </div>

            <!-- Documents Table -->
            <div class="documents-card">

                <div class="documents-table">

                    <div class="document-row document-row-header">
                        <span>Document</span>
                        <span>Vehicle</span>
                        <span>Expiry Date</span>
                        <span>Status</span>
                        <span></span>
                    </div>

                    <?php foreach ($documents as $document): ?>

                        <?php
                        $searchText = strtolower(
                            $document['document_type'] . ' '
                            . $document['vehicle_name'] . ' '
                            . ($document['registration_number'] ?? '') . ' '
                            . $document['description']
                        );

                        $vehicleIdForLink = $document['vehicle_id'] !== null
                            ? (int) $document['vehicle_id']
                            : null;

                        $renewUrl = 'add.php?id=' . (int) $document['id'];

                        if ($vehicleIdForLink !== null) {
                            $renewUrl .= '&vehicle_id=' . $vehicleIdForLink;
                        }
                        ?>

                        <div
                            class="document-row document-item"
                            data-type="<?= htmlspecialchars($document['type_key']) ?>"
                            data-status="<?= htmlspecialchars($document['status']) ?>"
                            data-search="<?= htmlspecialchars($searchText) ?>"
                        >

                            <div class="document-name-cell">
                                <div class="document-list-icon <?= htmlspecialchars($document['icon_class']) ?>">
                                    <i class="bi <?= htmlspecialchars($document['icon']) ?>"></i>
                                </div>

                                <div>
                                    <strong><?= htmlspecialchars($document['document_type']) ?></strong>
                                    <small><?= htmlspecialchars($document['description']) ?></small>
                                </div>
                            </div>

                            <div class="document-vehicle-cell">

                                <?php if ($vehicleIdForLink !== null): ?>
                                    <strong>
                                        <?= htmlspecialchars($document['vehicle_name']) ?>
                                    </strong>
                                    <small>
                                        <?= htmlspecialchars($document['registration_number'] ?? '') ?>
                                    </small>
                                <?php else: ?>
                                    <strong>Personal Document</strong>
                                    <small>Not linked to a vehicle</small>
                                <?php endif; ?>

                            </div>

                            <div class="document-expiry-cell">
                                <strong>
                                    <?= htmlspecialchars(
                                        (new DateTimeImmutable($document['expiry_date']))
                                            ->format('d M Y')
                                    ) ?>
                                </strong>

                                <small class="<?= htmlspecialchars($document['expiry_text_class']) ?>">
                                    <?= htmlspecialchars($document['expiry_message']) ?>
                                </small>
                            </div>

                            <div>
                                <span class="status-badge <?= htmlspecialchars($document['status_class']) ?>">
                                    <i class="bi <?= htmlspecialchars($document['status_icon']) ?>"></i>
                                    <?= htmlspecialchars($document['status_label']) ?>
                                </span>
                            </div>

                            <a
                                href="<?= htmlspecialchars($renewUrl) ?>"
                                class="document-view-btn"
                                aria-label="Renew <?= htmlspecialchars($document['document_type']) ?>"
                                title="Renew document"
                            >
                                <i class="bi bi-chevron-right"></i>
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

                <!-- Empty state -->
                <div
                    class="document-empty-state"
                    id="documentEmptyState"
                    style="<?= empty($documents) ? 'display:block;' : 'display:none;' ?>"
                >
                    <div class="empty-icon">
                        <i class="bi bi-files"></i>
                    </div>

                    <h3 id="emptyStateTitle">
                        <?= empty($documents) ? 'No documents added yet' : 'No documents found' ?>
                    </h3>

                    <p id="emptyStateMessage">
                        <?= empty($documents)
                            ? 'Add a document to start tracking its expiry date.'
                            : 'Try changing your search or filters.' ?>
                    </p>

                    <?php if (empty($documents)): ?>
                        <a href="add.php" class="secondary-action">
                            <i class="bi bi-plus-lg"></i>
                            Add Document
                        </a>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </main>

</div>

<script src="../assets/js/app.js"></script>

<script>
const documentSearch = document.getElementById('documentSearch');
const documentTypeFilter = document.getElementById('documentTypeFilter');
const documentStatusFilter = document.getElementById('documentStatusFilter');
const documentItems = document.querySelectorAll('.document-item');
const documentEmptyState = document.getElementById('documentEmptyState');
const emptyStateTitle = document.getElementById('emptyStateTitle');
const emptyStateMessage = document.getElementById('emptyStateMessage');

function filterDocuments() {
    const search = documentSearch.value.toLowerCase().trim();
    const type = documentTypeFilter.value;
    const status = documentStatusFilter.value;

    let visibleCount = 0;

    documentItems.forEach(item => {
        const matchesSearch = item.dataset.search.includes(search);
        const matchesType = type === 'all' || item.dataset.type === type;
        const matchesStatus = status === 'all' || item.dataset.status === status;

        if (matchesSearch && matchesType && matchesStatus) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    documentEmptyState.style.display = visibleCount === 0 ? 'block' : 'none';

    if (emptyStateTitle) {
        emptyStateTitle.textContent = 'No documents found';
    }

    if (emptyStateMessage) {
        emptyStateMessage.textContent = 'Try changing your search or filters.';
    }
}

documentSearch.addEventListener('input', filterDocuments);
documentTypeFilter.addEventListener('change', filterDocuments);
documentStatusFilter.addEventListener('change', filterDocuments);
</script>

</body>
</html>