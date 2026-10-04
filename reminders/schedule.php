<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Access denied. Run this script from the command line.');
}

/*
|--------------------------------------------------------------------------
| Vehicle Document Manager - Reminder Scheduler
|--------------------------------------------------------------------------
| Creates pending reminder records for current documents.
| This script does not send emails.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Colombo');

$today = new DateTimeImmutable('today');

/*
|--------------------------------------------------------------------------
| Reminder configuration
|--------------------------------------------------------------------------
*/

$reminderTypes = [
    'final_week' => 7,
    'expiry_day' => 0,
    'post_expiry' => -7,
];

/*
|--------------------------------------------------------------------------
| Helper: Get monthly reminder dates
|--------------------------------------------------------------------------
| Schedule monthly reminders before expiry, using the same day of the
| month where possible. If that day does not exist, use the last day
| of that month.
|--------------------------------------------------------------------------
*/

function getMonthlyReminderDates(
    DateTimeImmutable $expiryDate,
    DateTimeImmutable $today
): array {
    $dates = [];

    // Begin with the first day of the month after today.
    $month = $today->modify('first day of this month');

    // Include this month if the monthly reminder date hasn't passed.
    while ($month < $expiryDate) {
        $day = (int) $expiryDate->format('d');
        $lastDay = (int) $month->format('t');
        $day = min($day, $lastDay);

        $date = $month->setDate(
            (int) $month->format('Y'),
            (int) $month->format('m'),
            $day
        );

        if ($date >= $today && $date < $expiryDate) {
            $dates[] = $date->format('Y-m-d');
        }

        $month = $month->modify('first day of next month');
    }

    return array_values(array_unique($dates));
}


/*
|--------------------------------------------------------------------------
| Load current documents
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id, expiry_date
    FROM documents
    WHERE is_current = 1
";

$stmt = $pdo->query($sql);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$insertSql = "
    INSERT INTO reminder_logs (
        document_id,
        reminder_type,
        scheduled_for,
        status
    )
    VALUES (
        :document_id,
        :reminder_type,
        :scheduled_for,
        'pending'
    )
    ON DUPLICATE KEY UPDATE id = id
";

$insertStmt = $pdo->prepare($insertSql);

$created = 0;
$skipped = 0;

foreach ($documents as $document) {
    $documentId = (int) $document['id'];

    $expiryDate = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $document['expiry_date']
    );

    if (!$expiryDate) {
        continue;
    }

    $reminders = [];

    // Monthly reminders before expiry.
    foreach (getMonthlyReminderDates($expiryDate, $today) as $date) {
        $reminders[] = [
            'type' => 'monthly',
            'date' => $date,
        ];
    }

    // Seven days before, on expiry day, and seven days after.
    foreach ($reminderTypes as $type => $offset) {
        $date = $expiryDate->modify(
            ($offset >= 0 ? '+' : '') . $offset . ' days'
        );

        if ($type === 'post_expiry' && $today < $date) {
            // This is a future reminder, so it is still eligible.
        }

        $reminders[] = [
            'type' => $type,
            'date' => $date->format('Y-m-d'),
        ];
    }

    foreach ($reminders as $reminder) {
        $scheduledDate = new DateTimeImmutable($reminder['date']);

        // Do not create reminders for dates that have already passed.
        if ($scheduledDate < $today) {
            $skipped++;
            continue;
        }

        $insertStmt->execute([
            ':document_id' => $documentId,
            ':reminder_type' => $reminder['type'],
            ':scheduled_for' => $reminder['date'],
        ]);

        if ($insertStmt->rowCount() === 1) {
            $created++;
        } else {
            $skipped++;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Output
|--------------------------------------------------------------------------
*/

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=UTF-8');
}

echo "Reminder scheduling completed.\n";
echo "Current documents checked: " . count($documents) . "\n";
echo "Reminder records created: {$created}\n";
echo "Reminders skipped or already existing: {$skipped}\n";
