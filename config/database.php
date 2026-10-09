<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: '';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: '';
$username = getenv('DB_USER') ?: '';
$password = getenv('DB_PASSWORD') ?: '';

$caCertificate = getenv('DB_CA_CERT')
    ?: __DIR__ . '/../aiven-ca.pem';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,

    PDO::MYSQL_ATTR_SSL_CA => $caCertificate,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
];

try {
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        $options
    );
} catch (PDOException $e) {
    error_log(
        'Database connection failed: ' . $e->getMessage()
    );

    http_response_code(500);

    exit(
        'Unable to connect to the database. Please try again later.'
    );
}