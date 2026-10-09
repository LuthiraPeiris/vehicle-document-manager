<?php

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];
$vehicleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$vehicleId || $vehicleId < 1) {
    header('Location: index.php');
    exit;
}

try {
    // Only retrieve a vehicle owned by the logged-in user.
    $vehicleStmt = $pdo->prepare(
        'SELECT
            id,
            registration_number,
            vehicle_type,
            make,
            model,
            manufacturing_year
         FROM vehicles
         WHERE id = :vehicle_id
           AND user_id = :user_id
         LIMIT 1'
    );

    $vehicleStmt->execute([
        'vehicle_id' => $vehicleId,
        'user_id' => $userId
    ]);

    $vehicle = $vehicleStmt->fetch();

    // Do not reveal whether a vehicle belonging to another user exists.
    if (!$vehicle) {
        header('Location: index.php?not_found=1');
        exit;
    }

    // Retrieve current documents belonging to this vehicle and user.
    $documentStmt = $pdo->prepare(
        'SELECT
            id,
            document_type,
            issue_date,
            expiry_date,
            file_path,
            original_file_name,
            file_mime_type,
            file_size
         FROM documents
         WHERE vehicle_id = :vehicle_id
           AND user_id = :user_id
           AND is_current = 1
           AND document_type <> :personal_document
         ORDER BY expiry_date ASC, id DESC'
    );

    $documentStmt->execute([
        'vehicle_id' => $vehicleId,
        'user_id' => $userId,
        'personal_document' => 'Driving License'
    ]);

    $documents = $documentStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Vehicle details page error: ' . $e->getMessage());

    http_response_code(500);
    exit('Unable to load vehicle details right now. Please try again later.');
}

// Escape output before placing database values in HTML.
function viewEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format the vehicle name.
$vehicleName = trim(
    ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')
);

if ($vehicleName === '') {
    $vehicleName = ucfirst(str_replace('_', ' ', $vehicle['vehicle_type']));
}

// Document presentation settings.
$documentStyles = [
    'Revenue License' => [
        'icon' => 'bi-file-earmark-text',
        'color' => 'orange-icon',
        'description' => 'Vehicle licensing'
    ],
    'Vehicle Insurance' => [
        'icon' => 'bi-shield-check',
        'color' => 'purple-icon',
        'description' => 'Insurance coverage'
    ],
    'Emission Test Certificate' => [
        'icon' => 'bi-wind',
        'color' => 'green-icon',
        'description' => 'Emission compliance'
    ]
];

// Calculate expiry status using today's date.
$today = new DateTimeImmutable('today');

foreach ($documents as &$document) {
    $expiryDate = new DateTimeImmutable($document['expiry_date']);
    $daysRemaining = (int) $today->diff($expiryDate)->format('%r%a');

    $document['days_remaining'] = $daysRemaining;

    if ($daysRemaining < 0) {
        $document['status'] = 'expired';
        $document['status_label'] = 'Expired';
        $document['status_class'] = 'danger-badge';
        $document['status_icon'] = 'bi-x-circle';
        $document['remaining_text'] =
            'Expired ' . abs($daysRemaining) .
            (abs($daysRemaining) === 1 ? ' day ago' : ' days ago');
        $document['remaining_class'] = 'danger-text';

    } elseif ($daysRemaining === 0) {
        $document['status'] = 'attention';
        $document['status_label'] = 'Expires Today';
        $document['status_class'] = 'warning-badge';
        $document['status_icon'] = 'bi-exclamation-circle';
        $document['remaining_text'] = 'Expires today';
        $document['remaining_class'] = 'warning-text';

    } elseif ($daysRemaining <= 30) {
        $document['status'] = 'attention';
        $document['status_label'] = 'Expiring Soon';
        $document['status_class'] = 'warning-badge';
        $document['status_icon'] = 'bi-exclamation-circle';
        $document['remaining_text'] =
            $daysRemaining . ' days remaining';
        $document['remaining_class'] = 'warning-text';

    } else {
        $document['status'] = 'active';
        $document['status_label'] = 'Valid';
        $document['status_class'] = 'success';
        $document['status_icon'] = 'bi-check-circle';
        $document['remaining_text'] =
            $daysRemaining . ' days remaining';
        $document['remaining_class'] = '';
    }
}
unset($document);

// Summarize the current vehicle documents.
$totalDocuments = count($documents);
$attentionDocuments = 0;

foreach ($documents as $document) {
    if ($document['status'] !== 'active') {
        $attentionDocuments++;
    }
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

                    <div class="breadcrumb">

                        <a href="index.php">
                            My Vehicles
                        </a>

                        <i class="bi bi-chevron-right"></i>

                        <span>
                            <?= viewEscape($vehicle['registration_number']) ?>
                        </span>

                    </div>

                    <h1><?= viewEscape($vehicleName) ?></h1>

                    <p>
                        Vehicle registration:
                        <?= viewEscape($vehicle['registration_number']) ?>
                    </p>

                </div>

                <div class="vehicle-detail-actions">

                    <a href="index.php" class="secondary-action">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>

                    <a href="add.php?id=<?= (int) $vehicle['id'] ?>"
                       class="add-vehicle-btn">
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

                        <h2><?= viewEscape($vehicleName) ?></h2>

                        <div class="vehicle-meta">

                            <span>
                                <i class="bi bi-credit-card-2-front"></i>
                                <?= viewEscape($vehicle['registration_number']) ?>
                            </span>

                            <span>
                                <i class="bi bi-car-front"></i>
                                <?= viewEscape(ucfirst(str_replace(
                                    '_',
                                    ' ',
                                    $vehicle['vehicle_type']
                                ))) ?>
                            </span>

                            <span>
                                <i class="bi bi-calendar3"></i>
                                <?= $vehicle['manufacturing_year']
                                    ? viewEscape($vehicle['manufacturing_year'])
                                    : 'Year not provided' ?>
                            </span>

                        </div>

                    </div>

                </div>

                <div class="vehicle-health">

                    <span class="health-label">
                        Document Status
                    </span>

                    <?php if ($totalDocuments === 0): ?>

                        <span class="status-badge">
                            <i class="bi bi-file-earmark"></i>
                            No Documents
                        </span>

                    <?php elseif ($attentionDocuments > 0): ?>

                        <span class="status-badge warning-badge">
                            <i class="bi bi-exclamation-circle"></i>
                            Needs Attention
                        </span>

                    <?php else: ?>

                        <span class="status-badge success">
                            <i class="bi bi-check-circle"></i>
                            All Documents Active
                        </span>

                    <?php endif; ?>

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

                <a
                    href="../documents/add.php?vehicle_id=<?= (int) $vehicle['id'] ?>"
                    class="secondary-action"
                >
                    <i class="bi bi-plus-lg"></i>
                    Add Document
                </a>

            </div>

            <!-- Current Document Cards -->
            <?php if (empty($documents)): ?>

                <div class="empty-search" style="display: block;">

                    <div class="empty-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <h3>No documents added yet</h3>

                    <p>
                        Add a Revenue License, Vehicle Insurance, or
                        Emission Test Certificate for this vehicle.
                    </p>

                    <a
                        href="../documents/add.php?vehicle_id=<?= (int) $vehicle['id'] ?>"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-plus-lg"></i>
                        Add Vehicle Document
                    </a>

                </div>

            <?php else: ?>

                <div class="document-grid">

                    <?php foreach ($documents as $document): ?>

                        <?php
                        $style = $documentStyles[$document['document_type']]
                            ?? [
                                'icon' => 'bi-file-earmark-text',
                                'color' => 'blue-icon',
                                'description' => 'Vehicle document'
                            ];

                        $formattedExpiry = (
                            new DateTimeImmutable($document['expiry_date'])
                        )->format('d M Y');
                        ?>

                        <div class="document-card">

                            <div class="document-card-header">

                                <div class="document-type-icon <?= viewEscape($style['color']) ?>">
                                    <i class="bi <?= viewEscape($style['icon']) ?>"></i>
                                </div>

                                <div class="document-card-title">

                                    <h3>
                                        <?= viewEscape($document['document_type']) ?>
                                    </h3>

                                    <span>
                                        <?= viewEscape($style['description']) ?>
                                    </span>

                                </div>

                            </div>

                            <div class="document-date">

                                <span>Expiry Date</span>

                                <strong>
                                    <?= viewEscape($formattedExpiry) ?>
                                </strong>

                            </div>

                            <div class="document-status-line">

                                <span class="status-badge <?= viewEscape($document['status_class']) ?>">

                                    <i class="bi <?= viewEscape($document['status_icon']) ?>"></i>

                                    <?= viewEscape($document['status_label']) ?>

                                </span>

                                <span class="remaining-text <?= viewEscape($document['remaining_class']) ?>">
                                    <?= viewEscape($document['remaining_text']) ?>
                                </span>

                            </div>

                            <div class="document-card-footer" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">

                                <?php if (!empty($document['file_path'])): ?>
                                    <a
                                        href="../documents/file.php?id=<?= (int) $document['id'] ?>"
                                        class="document-action"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <i class="bi bi-eye"></i>
                                        View
                                    </a>

                                    <a
                                        href="../documents/file.php?id=<?= (int) $document['id'] ?>&download=1"
                                        class="document-action"
                                    >
                                        <i class="bi bi-download"></i>
                                        Download
                                    </a>
                                <?php else: ?>
                                    <span class="document-action" style="opacity:.65;cursor:default;">
                                        <i class="bi bi-paperclip"></i>
                                        No file uploaded
                                    </span>
                                <?php endif; ?>

                                <a
                                    href="../documents/add.php?id=<?= (int) $document['id'] ?>&vehicle_id=<?= (int) $vehicle['id'] ?>"
                                    class="document-action"
                                >
                                    <i class="bi bi-pencil"></i>
                                    Update
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

<script src="../assets/js/app.js"></script>

</body>
</html>