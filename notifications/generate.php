<?php
/**
 * Daily CLI job that creates in-app document expiry notifications.
 * It is intentionally separate from reminders/send.php and sends no email.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

date_default_timezone_set('Asia/Colombo');
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/date-rules.php';

$today = new DateTimeImmutable('today');
$monthRangeStart = $today->modify('+28 days')->format('Y-m-d');
$monthRangeEnd = $today->modify('+31 days')->format('Y-m-d');
$finalWeekDate = $today->modify('+7 days')->format('Y-m-d');
$todayDate = $today->format('Y-m-d');
$postExpiryDate = $today->modify('-7 days')->format('Y-m-d');

$candidates = $pdo->prepare(
    'SELECT d.id, d.user_id, d.document_type, d.expiry_date, v.registration_number
     FROM documents d
     LEFT JOIN vehicles v ON v.id = d.vehicle_id AND v.user_id = d.user_id
     WHERE d.is_current = 1
       AND (
            d.expiry_date BETWEEN :month_start AND :month_end
            OR d.expiry_date IN (:final_week, :expiry_day, :post_expiry)
       )'
);
$candidates->execute([
    'month_start' => $monthRangeStart,
    'month_end' => $monthRangeEnd,
    'final_week' => $finalWeekDate,
    'expiry_day' => $todayDate,
    'post_expiry' => $postExpiryDate,
]);

$insert = $pdo->prepare(
    'INSERT INTO notifications (
        user_id, document_id, document_type, vehicle_registration,
        milestone, expiry_date, days_offset, message
     ) VALUES (
        :user_id, :document_id, :document_type, :vehicle_registration,
        :milestone, :expiry_date, :days_offset, :message
     ) ON DUPLICATE KEY UPDATE id = id'
);
$created = 0;

foreach ($candidates->fetchAll() as $document) {
    $expiry = new DateTimeImmutable($document['expiry_date']);
    $milestones = notificationMilestonesForExpiry($expiry, $today);

    foreach ($milestones as $milestone => $description) {
        $vehicle = trim((string) ($document['registration_number'] ?? ''));
        $subject = $document['document_type'] . ($vehicle !== '' ? ' for ' . $vehicle : '');
        $message = sprintf(
            '%s %s (%s).',
            $subject,
            $description,
            $expiry->format('M j, Y')
        );
        $insert->execute([
            'user_id' => (int) $document['user_id'],
            'document_id' => (int) $document['id'],
            'document_type' => $document['document_type'],
            'vehicle_registration' => $vehicle !== '' ? $vehicle : null,
            'milestone' => $milestone,
            'expiry_date' => $expiry->format('Y-m-d'),
            'days_offset' => (int) $today->diff($expiry)->format('%r%a'),
            'message' => $message,
        ]);
        $created += $insert->rowCount() === 1 ? 1 : 0;
    }
}

fwrite(STDOUT, sprintf("Notification generation complete. %d new notification(s).\n", $created));
