<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/storage.php';
require_once __DIR__ . '/../config/cloud-notifications.php';

$userId = (int) $_SESSION['user_id'];
$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$documentId = ($documentId !== false && $documentId !== null && $documentId > 0) ? (int) $documentId : null;

if ($documentId === null) {
    http_response_code(404);
    exit('Document not found.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$errors = [];
$newStoredFilePath = null;

function fetchCurrentDocument(PDO $pdo, int $documentId, int $userId): array|false
{
    $stmt = $pdo->prepare(
    'SELECT
        d.*,
        v.registration_number,
        v.make,
        v.model,
        u.full_name AS user_name,
        u.email AS user_email
     FROM documents d
     INNER JOIN users u
        ON u.id = d.user_id
     LEFT JOIN vehicles v
        ON v.id = d.vehicle_id
        AND v.user_id = d.user_id
     WHERE d.id = :document_id
       AND d.user_id = :user_id
       AND d.is_current = 1
     LIMIT 1'
);
    $stmt->execute(['document_id' => $documentId, 'user_id' => $userId]);
    return $stmt->fetch();
}

$document = fetchCurrentDocument($pdo, $documentId, $userId);
if (!$document) {
    http_response_code(404);
    exit('Current document not found.');
}

$form = [
    'issue_date' => $document['issue_date'] ?? '',
    'expiry_date' => $document['expiry_date'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['issue_date'] = trim((string) ($_POST['issue_date'] ?? ''));
    $form['expiry_date'] = trim((string) ($_POST['expiry_date'] ?? ''));

    if (!isset($_POST['csrf_token']) || !hash_equals($csrfToken, (string) $_POST['csrf_token'])) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    $postedId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
    if ($postedId === false || $postedId === null || (int) $postedId !== $documentId) {
        $errors[] = 'Invalid document request. Please reopen the edit page.';
    }

    $issueDate = $form['issue_date'] !== '' ? $form['issue_date'] : null;
    $expiryDate = $form['expiry_date'];

    if ($issueDate !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $issueDate)
        || !checkdate((int) substr($issueDate, 5, 2), (int) substr($issueDate, 8, 2), (int) substr($issueDate, 0, 4)))) {
        $errors[] = 'Please enter a valid issue date.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)
        || !checkdate((int) substr($expiryDate, 5, 2), (int) substr($expiryDate, 8, 2), (int) substr($expiryDate, 0, 4))) {
        $errors[] = 'Please enter a valid expiry date.';
    }
    if ($issueDate !== null && $expiryDate !== '' && $issueDate > $expiryDate) {
        $errors[] = 'The issue date cannot be after the expiry date.';
    }

    $upload = null;
    if (isset($_FILES['document_file']) && is_array($_FILES['document_file'])) {
        $file = $_FILES['document_file'];
        $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_NO_FILE) {
            if ($uploadError !== UPLOAD_ERR_OK) {
                $errors[] = 'The file could not be uploaded. Please choose it again.';
            } elseif (!is_uploaded_file($file['tmp_name'] ?? '')) {
                $errors[] = 'The uploaded file could not be verified.';
            } elseif ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
                $errors[] = 'The file must be 5 MB or smaller.';
            } elseif ((int) ($file['size'] ?? 0) <= 0) {
                $errors[] = 'The selected file is empty.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $extensions = [
                    'application/pdf' => 'pdf',
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                ];
                if (!isset($extensions[$mime])) {
                    $errors[] = 'Only PDF, JPG, and PNG files are allowed.';
                } else {
                    $originalName = basename((string) ($file['name'] ?? 'document'));
                    $originalName = function_exists('mb_substr') ? mb_substr($originalName, 0, 255) : substr($originalName, 0, 255);
                    $upload = [
                        'tmp_name' => $file['tmp_name'],
                        'extension' => $extensions[$mime],
                        'original_file_name' => $originalName !== '' ? $originalName : 'document.' . $extensions[$mime],
                        'file_mime_type' => $mime,
                        'file_size' => (int) $file['size'],
                    ];
                }
            }
        }
    }

    if (!$errors) {
        try {
            $fileMetadata = [
                'file_path' => $document['file_path'] ?? null,
                'original_file_name' => $document['original_file_name'] ?? null,
                'file_mime_type' => $document['file_mime_type'] ?? null,
                'file_size' => $document['file_size'] ?? null,
            ];

            if ($upload !== null) {

    /*
     * Generate a unique R2 object key.
     */
    $storedName = bin2hex(random_bytes(24))
        . '.' . $upload['extension'];

    $objectKey = 'storage/' . $storedName;

    /*
     * Upload the replacement file directly to Cloudflare R2.
     */
    uploadDocumentToR2(
        $upload['tmp_name'],
        $objectKey,
        $upload['file_mime_type']
    );

    /*
     * Store the R2 object key in MySQL.
     */
    $fileMetadata = [
        'file_path' => $objectKey,
        'original_file_name' => $upload['original_file_name'],
        'file_mime_type' => $upload['file_mime_type'],
        'file_size' => $upload['file_size'],
    ];
}

            $update = $pdo->prepare(
                'UPDATE documents
                 SET issue_date = :issue_date,
                     expiry_date = :expiry_date,
                     file_path = :file_path,
                     original_file_name = :original_file_name,
                     file_mime_type = :file_mime_type,
                     file_size = :file_size,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :document_id AND user_id = :user_id AND is_current = 1'
            );
            $update->execute([
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate,
                'file_path' => $fileMetadata['file_path'],
                'original_file_name' => $fileMetadata['original_file_name'],
                'file_mime_type' => $fileMetadata['file_mime_type'],
                'file_size' => $fileMetadata['file_size'],
                'document_id' => $documentId,
                'user_id' => $userId,
            ]);

            if ($update->rowCount() === 0) {
                // MySQL can report zero when submitted values are unchanged; verify the row still exists.
                $verify = fetchCurrentDocument($pdo, $documentId, $userId);
                if (!$verify) {
                    throw new RuntimeException('Document is no longer available for editing.');
                }
            }

            // Synchronize the updated document with the cloud notification service.
$cloudDocument = fetchCurrentDocument($pdo, $documentId, $userId);

if ($cloudDocument) {
    syncDocumentToCloud([
        'local_document_id' => $cloudDocument['id'],
        'user_name' => $cloudDocument['user_name'],
        'user_email' => $cloudDocument['user_email'],
        'vehicle_registration' => $cloudDocument['registration_number'] ?? null,
        'document_type' => $cloudDocument['document_type'],
        'expiry_date' => $cloudDocument['expiry_date'],
        'is_current' => $cloudDocument['is_current'],
    ]);
}

            header('Location: index.php?edited=1');
            exit;
        } catch (Throwable $e) {

    error_log('Document edit failed: ' . $e->getMessage());

    $errors[] = 'The document could not be updated. Please try again.';
}
    }
}

$vehicleLabel = 'Personal document';
if (!empty($document['vehicle_id'])) {
    $vehicleLabel = trim(($document['make'] ?? '') . ' ' . ($document['model'] ?? ''));
    if ($vehicleLabel === '') $vehicleLabel = $document['registration_number'] ?? 'Vehicle';
    if (!empty($document['registration_number'])) $vehicleLabel .= ' (' . $document['registration_number'] . ')';
}
$pageTitle = 'Edit Document';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/navbar.php'; ?>
        <div class="content-wrapper">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="index.php">Documents</a><i class="bi bi-chevron-right"></i><span>Edit Document</span></div>
                    <h1>Edit Document</h1>
                    <p>Update the dates or replace the uploaded file. Document type and vehicle association cannot be changed here.</p>
                </div>
                <a href="index.php" class="secondary-action"><i class="bi bi-arrow-left"></i> Back to Documents</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <div class="form-card">
                <div class="form-card-header">
                    <div class="form-section-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <div><h2><?= htmlspecialchars($document['document_type']) ?></h2><p><?= htmlspecialchars($vehicleLabel) ?></p></div>
                </div>
                <form method="POST" action="edit.php?id=<?= $documentId ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="document_id" value="<?= $documentId ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="issue_date">Issue Date <small>(optional)</small></label>
                            <input class="form-control" type="date" id="issue_date" name="issue_date" value="<?= htmlspecialchars($form['issue_date']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date <span aria-hidden="true">*</span></label>
                            <input class="form-control" type="date" id="expiry_date" name="expiry_date" required value="<?= htmlspecialchars($form['expiry_date']) ?>">
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Current uploaded file</label>
                        <?php if (!empty($document['file_path'])): ?>
                            <p>
                                <i class="bi bi-paperclip"></i>
                                <?= htmlspecialchars($document['original_file_name'] ?? 'Uploaded document') ?>
                                <a href="file.php?id=<?= $documentId ?>" target="_blank" rel="noopener">View</a>
                                · <a href="file.php?id=<?= $documentId ?>&amp;download=1">Download</a>
                            </p>
                        <?php else: ?>
                            <p>No file uploaded yet.</p>
                        <?php endif; ?>
                        <label for="document_file">Replace file <small>(optional, PDF/JPG/PNG, maximum 5 MB)</small></label>
                        <input class="form-control" type="file" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                        <small class="form-text">Leave this empty to keep the current file.</small>
                    </div>

                    <div class="form-actions mt-4">
                        <a href="index.php" class="secondary-action">Cancel</a>
                        <button type="submit" class="primary-action"><i class="bi bi-check-lg"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
