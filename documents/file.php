<?php

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/storage.php';

$userId = (int) $_SESSION['user_id'];
$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$download = isset($_GET['download']) && $_GET['download'] === '1';

if (!$documentId || $documentId < 1) {
    http_response_code(404);
    exit('Document not found.');
}

try {
    // Only allow the logged-in owner to access this document.
    $stmt = $pdo->prepare(
        'SELECT id, file_path, original_file_name, file_mime_type, file_size
         FROM documents
         WHERE id = :document_id
           AND user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([
        'document_id' => $documentId,
        'user_id' => $userId
    ]);
    $document = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Document file lookup failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to retrieve this document right now.');
}

if (!$document || empty($document['file_path'])) {
    http_response_code(404);
    exit('Document file not found.');
}

// The database stores paths like storage/<random-filename>.
// Use only the basename so a modified database value cannot traverse directories.
$storedFilename = basename(str_replace('\\', '/', (string) $document['file_path']));
$storageRoot = realpath(DOCUMENT_STORAGE_PATH);
$filePath = $storageRoot !== false
    ? $storageRoot . DIRECTORY_SEPARATOR . $storedFilename
    : '';

if (
    $storageRoot === false ||
    $storedFilename === '' ||
    !is_file($filePath) ||
    !is_readable($filePath)
) {
    http_response_code(404);
    exit('Document file is unavailable.');
}

$allowedMimeTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png'
];

$detectedMime = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);
if (!in_array($detectedMime, $allowedMimeTypes, true)) {
    http_response_code(415);
    exit('This file type cannot be served.');
}

$originalName = trim((string) ($document['original_file_name'] ?? ''));
if ($originalName === '') {
    $originalName = 'document';
}

// Remove header/control characters and provide a safe fallback filename.
$originalName = str_replace(["\\r", "\\n", '"', "\\\\"], '', $originalName);
$asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $originalName);
if ($asciiName === '' || $asciiName === null) {
    $asciiName = 'document';
}

$disposition = $download ? 'attachment' : 'inline';
header('Content-Type: ' . $detectedMime);
header('Content-Length: ' . (string) filesize($filePath));
header('Content-Disposition: ' . $disposition . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($originalName));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filePath);
exit;
