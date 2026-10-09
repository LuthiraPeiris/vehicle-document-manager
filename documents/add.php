<?php

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/storage.php';
require_once __DIR__ . '/../config/cloud-notifications.php';

$userId = (int) $_SESSION['user_id'];

$allowedDocumentTypes = [
    'Driving License',
    'Revenue License',
    'Vehicle Insurance',
    'Emission Test Certificate',
];

$errors = [];
$successMessage = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$vehicleId = filter_input(INPUT_GET, 'vehicle_id', FILTER_VALIDATE_INT);
$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$vehicleId = $vehicleId !== false && $vehicleId !== null && $vehicleId > 0
    ? $vehicleId
    : null;

$documentId = $documentId !== false && $documentId !== null && $documentId > 0
    ? $documentId
    : null;

$renewingDocument = null;

/*
|--------------------------------------------------------------------------
| Load the user's vehicles
|--------------------------------------------------------------------------
*/

$vehicleQuery = $pdo->prepare(
    'SELECT id, registration_number, vehicle_type, make, model
     FROM vehicles
     WHERE user_id = :user_id
     ORDER BY registration_number ASC'
);

$vehicleQuery->execute(['user_id' => $userId]);
$vehicles = $vehicleQuery->fetchAll();

/*
|--------------------------------------------------------------------------
| Load a document when the user is renewing it
|--------------------------------------------------------------------------
*/

if ($documentId !== null) {
    $documentQuery = $pdo->prepare(
        'SELECT d.*
         FROM documents d
         WHERE d.id = :document_id
           AND d.user_id = :user_id
           AND d.is_current = 1
         LIMIT 1'
    );

    $documentQuery->execute([
        'document_id' => $documentId,
        'user_id' => $userId,
    ]);

    $renewingDocument = $documentQuery->fetch();

    if (!$renewingDocument) {
        header('Location: renewals.php?not_found=1');
        exit;
    }

    $vehicleId = $renewingDocument['vehicle_id'] !== null
        ? (int) $renewingDocument['vehicle_id']
        : null;
}

/*
|--------------------------------------------------------------------------
| Form defaults
|--------------------------------------------------------------------------
*/

$form = [
    'vehicle_id' => $vehicleId !== null ? (string) $vehicleId : '',
    'document_type' => $renewingDocument['document_type'] ?? '',
    'issue_date' => '',
    'expiry_date' => '',
];

if ($renewingDocument) {
    $form['issue_date'] = $renewingDocument['issue_date'] ?? '';
}

/*
|--------------------------------------------------------------------------
| Process form submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['vehicle_id'] = trim($_POST['vehicle_id'] ?? '');
    $form['document_type'] = trim($_POST['document_type'] ?? '');
    $form['issue_date'] = trim($_POST['issue_date'] ?? '');
    $form['expiry_date'] = trim($_POST['expiry_date'] ?? '');

    $postedDocumentId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);

    $postedDocumentId = $postedDocumentId !== false
        && $postedDocumentId !== null
        && $postedDocumentId > 0
            ? $postedDocumentId
            : null;

    /*
     * Optional document upload. The extension and MIME type are both checked
     * server-side; the browser's accept attribute is only a convenience.
     */
    $uploadedFile = null;
    $newStoredFilePath = null;
    $maxUploadBytes = 5 * 1024 * 1024;
    $allowedMimeTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    if (isset($_FILES['document_file']) && is_array($_FILES['document_file'])) {
        $file = $_FILES['document_file'];

        if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = $file['error'] === UPLOAD_ERR_INI_SIZE
                    || $file['error'] === UPLOAD_ERR_FORM_SIZE
                    ? 'The selected file is too large. Maximum size is 5 MB.'
                    : 'The file could not be uploaded. Please try again.';
            } elseif (!is_uploaded_file($file['tmp_name'])) {
                $errors[] = 'The uploaded file could not be verified.';
            } elseif ((int) $file['size'] <= 0 || (int) $file['size'] > $maxUploadBytes) {
                $errors[] = 'Choose a file smaller than or equal to 5 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $detectedMime = $finfo->file($file['tmp_name']);

                if (!isset($allowedMimeTypes[$detectedMime])) {
                    $errors[] = 'Only PDF, JPG, JPEG, and PNG files are allowed.';
                } else {
                    $originalName = basename((string) $file['name']);
                    $uploadedFile = [
                        'tmp_name' => $file['tmp_name'],
                        'mime_type' => $detectedMime,
                        'extension' => $allowedMimeTypes[$detectedMime],
                        'original_name' => mb_substr($originalName, 0, 255),
                        'size' => (int) $file['size'],
                    ];
                }
            }
        }
    }

    if (
        !isset($_POST['csrf_token'])
        || !hash_equals($csrfToken, $_POST['csrf_token'])
    ) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    /*
     * If this is a renewal, only allow the document originally loaded
     * for this user to be renewed.
     */
    if (
        ($documentId === null && $postedDocumentId !== null)
        || ($documentId !== null && $postedDocumentId !== $documentId)
    ) {
        $errors[] = 'Invalid document request. Please reopen the form.';
    }

    $documentType = $form['document_type'];

    if (!in_array($documentType, $allowedDocumentTypes, true)) {
        $errors[] = 'Please select a valid document type.';
    }

    $isDrivingLicense = $documentType === 'Driving License';

    $submittedVehicleId = null;

    if (!$isDrivingLicense) {
        $validatedVehicleId = filter_var(
            $form['vehicle_id'],
            FILTER_VALIDATE_INT
        );

        if (
            $validatedVehicleId === false
            || $validatedVehicleId <= 0
        ) {
            $errors[] = 'Please select a vehicle for this document.';
        } else {
            $submittedVehicleId = (int) $validatedVehicleId;

            $vehicleCheck = $pdo->prepare(
                'SELECT id
                 FROM vehicles
                 WHERE id = :vehicle_id
                   AND user_id = :user_id
                 LIMIT 1'
            );

            $vehicleCheck->execute([
                'vehicle_id' => $submittedVehicleId,
                'user_id' => $userId,
            ]);

            if (!$vehicleCheck->fetch()) {
                $errors[] = 'The selected vehicle could not be found.';
            }
        }
    }

    /*
     * Driving Licenses belong to the user, not a vehicle.
     */
    if ($isDrivingLicense) {
        $submittedVehicleId = null;
        $form['vehicle_id'] = '';
    }

    $issueDate = $form['issue_date'] !== ''
        ? $form['issue_date']
        : null;

    $expiryDate = $form['expiry_date'];

    if (
        $issueDate !== null
        && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issueDate)
    ) {
        $errors[] = 'Please enter a valid issue date.';
    }

    if (
        $issueDate !== null
        && !checkdate(
            (int) substr($issueDate, 5, 2),
            (int) substr($issueDate, 8, 2),
            (int) substr($issueDate, 0, 4)
        )
    ) {
        $errors[] = 'Please enter a valid issue date.';
    }

    if (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)
        || !checkdate(
            (int) substr($expiryDate, 5, 2),
            (int) substr($expiryDate, 8, 2),
            (int) substr($expiryDate, 0, 4)
        )
    ) {
        $errors[] = 'Please enter a valid expiry date.';
    }

    if (
        $issueDate !== null
        && $expiryDate !== ''
        && $issueDate > $expiryDate
    ) {
        $errors[] = 'The issue date cannot be after the expiry date.';
    }

    /*
     * Preserve the original document's type and vehicle during renewal.
     */
    if ($renewingDocument) {
        if ($documentType !== $renewingDocument['document_type']) {
            $errors[] = 'A renewal must use the same document type.';
        }

        $originalVehicleId = $renewingDocument['vehicle_id'] !== null
            ? (int) $renewingDocument['vehicle_id']
            : null;

        if ($submittedVehicleId !== $originalVehicleId) {
            $errors[] = 'A renewal must belong to the original vehicle.';
        }
    }

    /*
     * Do not allow a second current document of the same type
     * unless this submission is renewing the existing document.
     */
    if (empty($errors)) {
        if ($submittedVehicleId === null) {
            $duplicateQuery = $pdo->prepare(
                'SELECT id
                 FROM documents
                 WHERE user_id = :user_id
                   AND document_type = :document_type
                   AND vehicle_id IS NULL
                   AND is_current = 1
                   AND (:exclude_id_null IS NULL OR id <> :exclude_id_compare)
                 LIMIT 1'
            );
        } else {
            $duplicateQuery = $pdo->prepare(
                'SELECT id
                 FROM documents
                 WHERE user_id = :user_id
                   AND document_type = :document_type
                   AND vehicle_id = :vehicle_id
                   AND is_current = 1
                   AND (:exclude_id_null IS NULL OR id <> :exclude_id_compare)
                 LIMIT 1'
            );
        }

        $duplicateParams = [
            'user_id' => $userId,
            'document_type' => $documentType,
            'exclude_id_null' => $documentId,
            'exclude_id_compare' => $documentId,
        ];

        if ($submittedVehicleId !== null) {
            $duplicateParams['vehicle_id'] = $submittedVehicleId;
        }

        $duplicateQuery->execute($duplicateParams);

        if ($duplicateQuery->fetch()) {
            $errors[] = 'A current document of this type already exists. Use its renewal option to add the renewed document.';
        }
    }

    /*
     * Save a new document, or preserve history when renewing one.
     * The old file remains attached to the old historical record.
     */
    if (empty($errors)) {
        try {
            $fileMetadata = [
                'file_path' => null,
                'original_file_name' => null,
                'file_mime_type' => null,
                'file_size' => null,
            ];

            if ($uploadedFile !== null) {

    /*
     * Generate a random object name.
     *
     * The database will continue storing values such as:
     * storage/abcdef123456....pdf
     *
     * This same value becomes the R2 object key.
     */
    $storedName = bin2hex(random_bytes(24))
        . '.' . $uploadedFile['extension'];

    $objectKey = 'storage/' . $storedName;

    /*
     * Upload the temporary PHP upload directly to Cloudflare R2.
     */
    uploadDocumentToR2(
        $uploadedFile['tmp_name'],
        $objectKey,
        $uploadedFile['mime_type']
    );

    /*
     * Keep the R2 object key in the database.
     */
    $fileMetadata = [
        'file_path' => $objectKey,
        'original_file_name' => $uploadedFile['original_name'],
        'file_mime_type' => $uploadedFile['mime_type'],
        'file_size' => $uploadedFile['size'],
    ];

} elseif ($renewingDocument) {
                $deactivateOld = $pdo->prepare(
                    'UPDATE documents
                     SET is_current = 0
                     WHERE id = :document_id
                       AND user_id = :user_id
                       AND is_current = 1'
                );

                $deactivateOld->execute([
                    'document_id' => $documentId,
                    'user_id' => $userId,
                ]);

                if ($deactivateOld->rowCount() !== 1) {
                    throw new RuntimeException(
                        'The original document could not be updated.'
                    );
                }
            }

            $insert = $pdo->prepare(
                'INSERT INTO documents (
                    user_id,
                    vehicle_id,
                    document_type,
                    issue_date,
                    expiry_date,
                    previous_document_id,
                    is_current,
                    file_path,
                    original_file_name,
                    file_mime_type,
                    file_size
                ) VALUES (
                    :user_id,
                    :vehicle_id,
                    :document_type,
                    :issue_date,
                    :expiry_date,
                    :previous_document_id,
                    1,
                    :file_path,
                    :original_file_name,
                    :file_mime_type,
                    :file_size
                )'
            );

            $insert->execute([
                'user_id' => $userId,
                'vehicle_id' => $submittedVehicleId,
                'document_type' => $documentType,
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate,
                'previous_document_id' => $documentId,
                'file_path' => $fileMetadata['file_path'],
                'original_file_name' => $fileMetadata['original_file_name'],
                'file_mime_type' => $fileMetadata['file_mime_type'],
                'file_size' => $fileMetadata['file_size'],
            ]);

            $newDocumentId = (int) $pdo->lastInsertId();
            $pdo->commit();

            // Synchronize the new document with the cloud notification service.
$cloudDocumentQuery = $pdo->prepare(
    'SELECT
        d.id,
        d.document_type,
        d.expiry_date,
        d.is_current,
        u.full_name AS user_name,
        u.email AS user_email,
        v.registration_number
     FROM documents d
     INNER JOIN users u
        ON u.id = d.user_id
     LEFT JOIN vehicles v
        ON v.id = d.vehicle_id
        AND v.user_id = d.user_id
     WHERE d.id = :document_id
       AND d.user_id = :user_id
     LIMIT 1'
);

$cloudDocumentQuery->execute([
    'document_id' => $newDocumentId,
    'user_id' => $userId,
]);

$cloudDocument = $cloudDocumentQuery->fetch();

if ($cloudDocument) {
    syncDocumentToCloud([
        'local_document_id' => $cloudDocument['id'],
        'user_name' => $cloudDocument['user_name'],
        'user_email' => $cloudDocument['user_email'],
        'vehicle_registration' => $cloudDocument['registration_number'],
        'document_type' => $cloudDocument['document_type'],
        'expiry_date' => $cloudDocument['expiry_date'],
        'is_current' => $cloudDocument['is_current'],
    ]);
}

if ($renewingDocument) {
    syncDocumentToCloud([
        'local_document_id' => $renewingDocument['id'],
        'user_name' => $cloudDocument['user_name'],
        'user_email' => $cloudDocument['user_email'],
        'vehicle_registration' => $cloudDocument['registration_number'],
        'document_type' => $renewingDocument['document_type'],
        'expiry_date' => $renewingDocument['expiry_date'],
        'is_current' => 0,
    ]);
}

            /*
             * Driving Licenses are user-level documents.
             * Other documents return to their vehicle details page.
             */
            if ($isDrivingLicense) {
                header('Location: index.php?saved=1');
            } else {
                header(
                    'Location: ../vehicles/view.php?id='
                    . $submittedVehicleId
                    . '&document_saved=1'
                );
            }

            exit;
        } catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Document save failed: ' . $e->getMessage());

    $errors[] = 'The document could not be saved. Please try again.';
}
    }
}

$isRenewal = $renewingDocument !== null;
$pageTitle = $isRenewal ? 'Renew Document' : 'Add Document';

?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="app-container">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../includes/navbar.php'; ?>

        <div class="content-wrapper">

            <div class="page-header">

                <div>

                    <div class="breadcrumb">

                        <a href="../vehicles/index.php">My Vehicles</a>

                        <i class="bi bi-chevron-right"></i>

                        <span><?= htmlspecialchars($pageTitle) ?></span>

                    </div>

                    <h1><?= htmlspecialchars($pageTitle) ?></h1>

                    <p>
                        <?= $isRenewal
                            ? 'Enter the renewed document dates. Your previous record will be preserved.'
                            : 'Add an expiry date for one of your vehicle documents or your Driving License.' ?>
                    </p>

                </div>

                <a
                    href="<?= $form['vehicle_id'] !== ''
                        ? '../vehicles/view.php?id=' . (int) $form['vehicle_id']
                        : '../vehicles/index.php' ?>"
                    class="secondary-action"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back
                </a>

            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="form-card document-form-card">

                <div class="form-card-header">

                    <div class="form-section-icon">
                        <i class="bi bi-file-earmark-plus"></i>
                    </div>

                    <div>
                        <h2>Document Information</h2>

                        <p>
                            Enter the document information below.
                        </p>
                    </div>

                </div>

                <form id="documentForm" method="POST" action="" enctype="multipart/form-data">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken) ?>"
                    >

                    <input
                        type="hidden"
                        name="document_id"
                        value="<?= $isRenewal ? (int) $documentId : '' ?>"
                    >

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="document_type">
                                Document Type
                                <span>*</span>
                            </label>

                            <select
                                id="document_type"
                                name="document_type"
                                required
                                <?= $isRenewal ? 'disabled' : '' ?>
                            >
                                <option value="">Select document type</option>

                                <?php foreach ($allowedDocumentTypes as $type): ?>
                                    <option
                                        value="<?= htmlspecialchars($type) ?>"
                                        <?= $form['document_type'] === $type ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if ($isRenewal): ?>
                                <input
                                    type="hidden"
                                    name="document_type"
                                    value="<?= htmlspecialchars($form['document_type']) ?>"
                                >
                            <?php endif; ?>

                        </div>

                        <div
                            class="form-group"
                            id="vehicleGroup"
                            <?= $form['document_type'] === 'Driving License' ? 'hidden' : '' ?>
                        >

                            <label for="vehicle_id">
                                Vehicle
                                <span>*</span>
                            </label>

                            <select
                                id="vehicle_id"
                                name="vehicle_id"
                                <?= $isRenewal || $form['document_type'] === 'Driving License' ? 'disabled' : '' ?>
                            >
                                <option value="">Select vehicle</option>

                                <?php foreach ($vehicles as $vehicle): ?>
                                    <?php
                                    $vehicleLabel = trim(
                                        ($vehicle['make'] ?? '') . ' '
                                        . ($vehicle['model'] ?? '')
                                    );

                                    if ($vehicleLabel === '') {
                                        $vehicleLabel = ucfirst($vehicle['vehicle_type']);
                                    }

                                    $vehicleLabel .= ' — ' . $vehicle['registration_number'];
                                    ?>

                                    <option
                                        value="<?= (int) $vehicle['id'] ?>"
                                        <?= (string) $vehicle['id'] === $form['vehicle_id'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($vehicleLabel) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>

                            <?php if ($isRenewal && $form['vehicle_id'] !== ''): ?>
                                <input
                                    type="hidden"
                                    name="vehicle_id"
                                    value="<?= (int) $form['vehicle_id'] ?>"
                                >
                            <?php endif; ?>

                            <?php if (!$vehicles && !$isRenewal): ?>
                                <small>
                                    You need to add a vehicle before adding vehicle documents.
                                    <a href="../vehicles/add.php">Add a vehicle</a>.
                                </small>
                            <?php endif; ?>

                        </div>

                        <div class="form-group">

                            <label for="issue_date">Issue Date</label>

                            <input
                                type="date"
                                id="issue_date"
                                name="issue_date"
                                value="<?= htmlspecialchars($form['issue_date']) ?>"
                                max="<?= date('Y-m-d') ?>"
                            >

                        </div>

                        <div class="form-group">

                            <label for="expiry_date">
                                Expiry Date
                                <span>*</span>
                            </label>

                            <input
                                type="date"
                                id="expiry_date"
                                name="expiry_date"
                                value="<?= htmlspecialchars($form['expiry_date']) ?>"
                                required
                            >

                            <small>
                                This date will be used to track renewals.
                            </small>

                        </div>

                    </div>

                    <div class="form-group document-upload-group">
                        <label for="document_file">Upload Document <small>(optional)</small></label>
                        <input
                            type="file"
                            id="document_file"
                            name="document_file"
                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        >
                        <small>Allowed formats: PDF, JPG, JPEG, PNG. Maximum file size: 5 MB.</small>

                        <?php if ($isRenewal && !empty($renewingDocument['original_file_name'])): ?>
                            <p class="mt-2 mb-0">
                                Current file:
                                <strong><?= htmlspecialchars($renewingDocument['original_file_name']) ?></strong>
                            </p>
                            <small>Upload a new file to replace the attachment for this renewed record, or leave this field empty to keep the existing attachment.</small>
                        <?php endif; ?>
                    </div>

                    <div class="document-form-notice">

                        <div class="document-form-notice-icon">
                            <i class="bi bi-bell"></i>
                        </div>

                        <div>

                            <h3>Expiry Reminders</h3>

                            <p>
                                The expiry date will be used to identify upcoming renewals.
                                Email reminders will be connected in a later step.
                            </p>

                        </div>

                    </div>

                    <div class="form-actions">

                        <a
                            href="<?= $form['vehicle_id'] !== ''
                                ? '../vehicles/view.php?id=' . (int) $form['vehicle_id']
                                : '../vehicles/index.php' ?>"
                            class="btn-cancel"
                        >
                            Cancel
                        </a>

                        <button type="submit" class="btn-save">
                            <i class="bi bi-check-lg"></i>
                            <?= $isRenewal ? 'Save Renewal' : 'Save Document' ?>
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>

<script src="../assets/js/app.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const documentType = document.getElementById('document_type');
    const vehicleGroup = document.getElementById('vehicleGroup');
    const vehicleSelect = document.getElementById('vehicle_id');
    const issueDate = document.getElementById('issue_date');
    const expiryDate = document.getElementById('expiry_date');

    function updateVehicleField() {
        if (!documentType || !vehicleGroup || !vehicleSelect) {
            return;
        }

        const isDrivingLicense = documentType.value === 'Driving License';

        vehicleGroup.hidden = isDrivingLicense;

        if (!<?= $isRenewal ? 'true' : 'false' ?>) {
            vehicleSelect.disabled = isDrivingLicense;
            vehicleSelect.required = !isDrivingLicense;

            if (isDrivingLicense) {
                vehicleSelect.value = '';
            }
        }
    }

    function updateDateLimits() {
        if (issueDate && expiryDate) {
            issueDate.max = expiryDate.value || '<?= date('Y-m-d') ?>';
        }
    }

    if (documentType) {
        documentType.addEventListener('change', updateVehicleField);
        updateVehicleField();
    }

    if (expiryDate) {
        expiryDate.addEventListener('change', updateDateLimits);
    }

    if (issueDate) {
        issueDate.addEventListener('change', updateDateLimits);
    }

    updateDateLimits();
});
</script>

</body>
</html>