
<?php

require_once __DIR__ . '/config/database.php';

try {
    $stmt = $pdo->query('SELECT DATABASE() AS database_name');
    $result = $stmt->fetch();

    echo 'Database connection successful!<br>';
    echo 'Connected database: '
        . htmlspecialchars($result['database_name'], ENT_QUOTES, 'UTF-8');
} catch (PDOException $e) {
    error_log('Database test failed: ' . $e->getMessage());

    http_response_code(500);
    echo 'Database test failed. Check the PHP error log.';
}