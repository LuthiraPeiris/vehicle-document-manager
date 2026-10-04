<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function notificationJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId === null || $userId < 1) {
    notificationJson(401, ['error' => 'Authentication required.']);
}

require_once __DIR__ . '/../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $count->execute(['user_id' => $userId]);
        $unreadCount = (int) $count->fetchColumn();

        $list = $pdo->prepare(
            'SELECT n.id, n.document_id, n.document_type, n.vehicle_registration,
                    n.milestone, n.expiry_date, n.days_offset, n.message, n.is_read, n.created_at,
                    d.id AS accessible_document_id, d.vehicle_id, d.is_current, d.file_path
             FROM notifications n
             LEFT JOIN documents d ON d.id = n.document_id AND d.user_id = n.user_id
             WHERE n.user_id = :user_id
             ORDER BY n.is_read ASC, n.created_at DESC, n.id DESC
             LIMIT 25'
        );
        $list->execute(['user_id' => $userId]);
        $items = [];
        foreach ($list->fetchAll() as $row) {
            if ($row['accessible_document_id'] === null) {
                $url = '/vehicle-document-manager/documents/index.php';
            } elseif (!(bool) $row['is_current']) {
                $url = '/vehicle-document-manager/documents/renewal_history.php';
            } elseif (!empty($row['file_path'])) {
                $url = '/vehicle-document-manager/documents/file.php?id=' . (int) $row['accessible_document_id'];
            } elseif ($row['vehicle_id'] !== null) {
                $url = '/vehicle-document-manager/vehicles/view.php?id=' . (int) $row['vehicle_id'];
            } else {
                $url = '/vehicle-document-manager/documents/index.php';
            }
            $row['id'] = (int) $row['id'];
            $row['is_read'] = (bool) $row['is_read'];
            $row['url'] = $url;
            $items[] = $row;
        }
        notificationJson(200, ['unread_count' => $unreadCount, 'notifications' => $items]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        notificationJson(405, ['error' => 'Method not allowed.']);
    }

    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    $requestToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
        notificationJson(403, ['error' => 'Invalid request token.']);
    }

    $body = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($body)) {
        notificationJson(400, ['error' => 'Invalid request.']);
    }

    if (($body['action'] ?? '') === 'mark_all_read') {
        $update = $pdo->prepare(
            'UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, CURRENT_TIMESTAMP)
             WHERE user_id = :user_id AND is_read = 0'
        );
        $update->execute(['user_id' => $userId]);
    } elseif (($body['action'] ?? '') === 'mark_read') {
        $notificationId = filter_var($body['notification_id'] ?? null, FILTER_VALIDATE_INT);
        if ($notificationId === false || $notificationId === null || $notificationId < 1) {
            notificationJson(400, ['error' => 'Invalid notification ID.']);
        }
        $update = $pdo->prepare(
            'UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, CURRENT_TIMESTAMP)
             WHERE id = :id AND user_id = :user_id AND is_read = 0'
        );
        $update->execute(['id' => $notificationId, 'user_id' => $userId]);
        if ($update->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT 1 FROM notifications WHERE id = :id AND user_id = :user_id');
            $exists->execute(['id' => $notificationId, 'user_id' => $userId]);
            if (!$exists->fetchColumn()) {
                notificationJson(404, ['error' => 'Notification not found.']);
            }
        }
    } else {
        notificationJson(400, ['error' => 'Unknown action.']);
    }

    $count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
    $count->execute(['user_id' => $userId]);
    notificationJson(200, ['unread_count' => (int) $count->fetchColumn()]);
} catch (Throwable $error) {
    error_log('Notification API error: ' . $error->getMessage());
    notificationJson(500, ['error' => 'Notifications are temporarily unavailable.']);
}
