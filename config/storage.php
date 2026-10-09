<?php

declare(strict_types=1);

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Cloudflare R2 configuration
|--------------------------------------------------------------------------
|
| These values are supplied through environment variables.
| Never place R2 credentials directly in this file.
|
*/

define(
    'R2_ENDPOINT',
    trim((string) (getenv('R2_ENDPOINT') ?: ''))
);

define(
    'R2_ACCESS_KEY_ID',
    trim((string) (getenv('R2_ACCESS_KEY_ID') ?: ''))
);

define(
    'R2_SECRET_ACCESS_KEY',
    trim((string) (getenv('R2_SECRET_ACCESS_KEY') ?: ''))
);

define(
    'R2_BUCKET',
    trim((string) (getenv('R2_BUCKET') ?: 'vehicle-document-manager-files'))
);

define(
    'R2_REGION',
    trim((string) (getenv('R2_REGION') ?: 'auto'))
);


/*
|--------------------------------------------------------------------------
| Cloudflare R2 client
|--------------------------------------------------------------------------
*/

function getR2Client(): S3Client
{
    static $client = null;

    if ($client instanceof S3Client) {
        return $client;
    }

    if (
        R2_ENDPOINT === '' ||
        R2_ACCESS_KEY_ID === '' ||
        R2_SECRET_ACCESS_KEY === '' ||
        R2_BUCKET === ''
    ) {
        throw new RuntimeException(
            'Cloudflare R2 storage is not configured.'
        );
    }

    $client = new S3Client([
        'version' => 'latest',
        'region' => R2_REGION,
        'endpoint' => R2_ENDPOINT,
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key' => R2_ACCESS_KEY_ID,
            'secret' => R2_SECRET_ACCESS_KEY,
        ],
    ]);

    return $client;
}


/*
|--------------------------------------------------------------------------
| Upload document to Cloudflare R2
|--------------------------------------------------------------------------
|
| $localFilePath is the temporary PHP upload file.
| $objectKey is the key that will also be stored in MySQL.
|
*/

function uploadDocumentToR2(
    string $localFilePath,
    string $objectKey,
    string $mimeType
): void {
    if (!is_file($localFilePath) || !is_readable($localFilePath)) {
        throw new RuntimeException(
            'The document file could not be read.'
        );
    }

    $objectKey = getR2ObjectKey($objectKey);

    if ($objectKey === '') {
        throw new RuntimeException(
            'Invalid R2 object key.'
        );
    }

    $fileHandle = fopen($localFilePath, 'rb');

    if ($fileHandle === false) {
        throw new RuntimeException(
            'Unable to open the document for storage.'
        );
    }

    try {
        getR2Client()->putObject([
            'Bucket' => R2_BUCKET,
            'Key' => $objectKey,
            'Body' => $fileHandle,
            'ContentType' => $mimeType,
        ]);
    } catch (AwsException $e) {
        error_log(
            'R2 upload failed: ' . $e->getMessage()
        );

        throw new RuntimeException(
            'Unable to store the document.'
        );
    } finally {
        fclose($fileHandle);
    }
}


/*
|--------------------------------------------------------------------------
| Retrieve document from Cloudflare R2
|--------------------------------------------------------------------------
|
| Returns the PSR stream returned by the AWS SDK.
|
*/

function getDocumentFromR2(string $objectKey)
{
    $objectKey = getR2ObjectKey($objectKey);

    if ($objectKey === '') {
        throw new RuntimeException(
            'Invalid R2 object key.'
        );
    }

    try {
        $result = getR2Client()->getObject([
            'Bucket' => R2_BUCKET,
            'Key' => $objectKey,
        ]);

        return $result['Body'];
    } catch (AwsException $e) {
        error_log(
            'R2 download failed: ' . $e->getMessage()
        );

        throw new RuntimeException(
            'Unable to retrieve the document.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Get R2 object metadata
|--------------------------------------------------------------------------
*/

function getDocumentMetadataFromR2(string $objectKey): array
{
    $objectKey = getR2ObjectKey($objectKey);

    if ($objectKey === '') {
        throw new RuntimeException(
            'Invalid R2 object key.'
        );
    }

    try {
        $result = getR2Client()->headObject([
            'Bucket' => R2_BUCKET,
            'Key' => $objectKey,
        ]);

        return [
            'content_length' => isset($result['ContentLength'])
                ? (int) $result['ContentLength']
                : null,

            'content_type' => isset($result['ContentType'])
                ? (string) $result['ContentType']
                : null,
        ];
    } catch (AwsException $e) {
        error_log(
            'R2 metadata lookup failed: ' . $e->getMessage()
        );

        throw new RuntimeException(
            'Unable to retrieve document information.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Check whether a document exists in R2
|--------------------------------------------------------------------------
*/

function documentExistsInR2(string $objectKey): bool
{
    $objectKey = getR2ObjectKey($objectKey);

    if ($objectKey === '') {
        return false;
    }

    try {
        getR2Client()->headObject([
            'Bucket' => R2_BUCKET,
            'Key' => $objectKey,
        ]);

        return true;
    } catch (AwsException $e) {
        return false;
    }
}


/*
|--------------------------------------------------------------------------
| Delete document from R2
|--------------------------------------------------------------------------
|
| This function is intentionally not called automatically when replacing
| documents. Historical document records may still reference their files.
|
*/

function deleteDocumentFromR2(string $objectKey): void
{
    $objectKey = getR2ObjectKey($objectKey);

    if ($objectKey === '') {
        return;
    }

    try {
        getR2Client()->deleteObject([
            'Bucket' => R2_BUCKET,
            'Key' => $objectKey,
        ]);
    } catch (AwsException $e) {
        error_log(
            'R2 delete failed: ' . $e->getMessage()
        );

        throw new RuntimeException(
            'Unable to delete the document.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Convert database file path to R2 object key
|--------------------------------------------------------------------------
|
| Existing database values look like:
|
| storage/abcdef123456.pdf
|
| We keep this format so the database does not need to change.
|
*/

function getR2ObjectKey(string $filePath): string
{
    $filePath = str_replace('\\', '/', trim($filePath));

    return ltrim($filePath, '/');
}