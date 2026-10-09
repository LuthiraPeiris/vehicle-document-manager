<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/storage.php';

$userId = (int) $_SESSION['user_id'];

$documentId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$download = isset($_GET['download'])
    && $_GET['download'] === '1';

if (!$documentId || $documentId < 1) {
    http_response_code(404);
    exit('Document not found.');
}

try {
    /*
     * Only allow the logged-in owner to access the document.
     */
    $stmt = $pdo->prepare(
        'SELECT
            id,
            file_path,
            original_file_name,
            file_mime_type,
            file_size
         FROM documents
         WHERE id = :document_id
           AND user_id = :user_id
         LIMIT 1'
    );

    $stmt->execute([
        'document_id' => $documentId,
        'user_id' => $userId,
    ]);

    $document = $stmt->fetch();

} catch (PDOException $e) {

    error_log(
        'Document file lookup failed: ' . $e->getMessage()
    );

    http_response_code(500);
    exit(
        'Unable to retrieve this document right now.'
    );
}

if (!$document || empty($document['file_path'])) {
    http_response_code(404);
    exit('Document file not found.');
}


/*
|--------------------------------------------------------------------------
| Convert database path to R2 object key
|--------------------------------------------------------------------------
*/

try {

    $objectKey = getR2ObjectKey(
        (string) $document['file_path']
    );

    if ($objectKey === '') {
        throw new RuntimeException(
            'Invalid document storage key.'
        );
    }

} catch (Throwable $e) {

    error_log(
        'Invalid R2 document key: ' . $e->getMessage()
    );

    http_response_code(404);
    exit('Document file is unavailable.');
}


/*
|--------------------------------------------------------------------------
| Retrieve the document from private R2 storage
|--------------------------------------------------------------------------
*/

try {

    $documentStream = getDocumentFromR2(
        $objectKey
    );

} catch (Throwable $e) {

    error_log(
        'R2 document retrieval failed: ' . $e->getMessage()
    );

    http_response_code(404);
    exit('Document file is unavailable.');
}


/*
|--------------------------------------------------------------------------
| Determine MIME type
|--------------------------------------------------------------------------
|
| We only allow the document types accepted by the application.
|
*/

$allowedMimeTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
];

$storedMime = trim(
    (string) ($document['file_mime_type'] ?? '')
);

if (!in_array($storedMime, $allowedMimeTypes, true)) {

    http_response_code(415);
    exit('This file type cannot be served.');
}


/*
|--------------------------------------------------------------------------
| Prepare safe download filename
|--------------------------------------------------------------------------
*/

$originalName = trim(
    (string) ($document['original_file_name'] ?? '')
);

if ($originalName === '') {
    $originalName = 'document';
}


/*
 * Remove characters that could break HTTP headers.
 */
$originalName = str_replace(
    ["\r", "\n", '"', "\\"],
    '',
    $originalName
);


/*
 * ASCII fallback filename.
 */
$asciiName = preg_replace(
    '/[^A-Za-z0-9._ -]/',
    '_',
    $originalName
);

if ($asciiName === '' || $asciiName === null) {
    $asciiName = 'document';
}


/*
|--------------------------------------------------------------------------
| Send document to browser
|--------------------------------------------------------------------------
*/

$disposition = $download
    ? 'attachment'
    : 'inline';

header(
    'Content-Type: ' . $storedMime
);

if (
    isset($document['file_size'])
    && (int) $document['file_size'] >= 0
) {
    header(
        'Content-Length: ' .
        (string) ((int) $document['file_size'])
    );
}

header(
    'Content-Disposition: ' .
    $disposition .
    '; filename="' .
    $asciiName .
    '"; filename*=UTF-8\'\'' .
    rawurlencode($originalName)
);

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'Cache-Control: private, no-store, no-cache, must-revalidate'
);

header(
    'Pragma: no-cache'
);


/*
|--------------------------------------------------------------------------
| Stream R2 object to the browser
|--------------------------------------------------------------------------
*/

while (!$documentStream->eof()) {

    echo $documentStream->read(8192);

    if (connection_aborted()) {
        break;
    }
}

exit;