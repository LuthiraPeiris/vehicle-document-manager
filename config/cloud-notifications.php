<?php

declare(strict_types=1);

if (!defined('CLOUD_NOTIFICATION_WORKER_URL')) {
    define(
        'CLOUD_NOTIFICATION_WORKER_URL',
        getenv('CLOUD_NOTIFICATION_WORKER_URL')
            ?: 'https://vehicle-document-notifications.luthirapeiris1.workers.dev'
    );
}

if (!defined('CLOUD_NOTIFICATION_API_KEY')) {
    define(
        'CLOUD_NOTIFICATION_API_KEY',
        getenv('CLOUD_NOTIFICATION_API_KEY') ?: ''
    );
}


function syncDocumentToCloud(array $document): bool
{
    $url = rtrim(CLOUD_NOTIFICATION_WORKER_URL, '/')
        . '/api/documents/sync';

    $payload = json_encode([
        'local_document_id' => (int) $document['local_document_id'],
        'user_name' => (string) $document['user_name'],
        'user_email' => (string) $document['user_email'],
        'vehicle_registration' => $document['vehicle_registration'] !== null
            ? (string) $document['vehicle_registration']
            : null,
        'document_type' => (string) $document['document_type'],
        'expiry_date' => (string) $document['expiry_date'],
        'is_current' => isset($document['is_current'])
            ? (int) $document['is_current']
            : 1,
    ], JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        error_log('Cloud notification sync failed: JSON encoding error.');
        return false;
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . CLOUD_NOTIFICATION_API_KEY,
        ],
        CURLOPT_POSTFIELDS => $payload,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        error_log(
            'Cloud notification sync failed: ' . curl_error($ch)
        );

        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log(
            'Cloud notification sync failed. HTTP ' .
            $httpCode . ' Response: ' . $response
        );

        return false;
    }

    return true;
}