<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Colombo');

// This script must run from the command line only.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Access denied. Run this script from the command line.');
}

use PHPMailer\PHPMailer\PHPMailer;

// Prevent two copies of this script from running simultaneously.
$lockPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vehicle-document-manager-send.lock';
$lockHandle = fopen($lockPath, 'c');

if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    if (is_resource($lockHandle)) {
        fclose($lockHandle);
    }

    exit("Another reminder-sending process is already running.\n");
}

try {
    // Load the database connection.
    require_once __DIR__ . '/../config/database.php';

    // Load PHPMailer installed by Composer.
    require_once __DIR__ . '/../vendor/autoload.php';

    // Load configuration
    $brevoApiKey = trim((string) (getenv('BREVO_API_KEY') ?: ''));

    $mailConfig = [];
    $mailConfigPath = 'C:\\xampp\\private\\vehicle-document-manager-mail.php';

    if (is_file($mailConfigPath)) {
        $mailConfig = require $mailConfigPath;
    }

    $fromEmail = getenv('SMTP_FROM_EMAIL') ?: ($mailConfig['from_email'] ?? ($mailConfig['username'] ?? ''));
    $fromName  = getenv('SMTP_FROM_NAME') ?: ($mailConfig['from_name'] ?? 'VehicleCare');

    if ($brevoApiKey === '') {
        $mailConfig = [
            'host' => getenv('SMTP_HOST') ?: ($mailConfig['host'] ?? ''),
            'port' => (int) (getenv('SMTP_PORT') ?: ($mailConfig['port'] ?? 587)),
            'encryption' => strtolower((string) (getenv('SMTP_ENCRYPTION') ?: ($mailConfig['encryption'] ?? 'tls'))),
            'username' => getenv('SMTP_USER') ?: ($mailConfig['username'] ?? ''),
            'password' => getenv('SMTP_PASSWORD') ?: ($mailConfig['password'] ?? ''),
            'from_email' => $fromEmail,
            'from_name' => $fromName,
        ];

        foreach (['host', 'port', 'encryption', 'username', 'password', 'from_email', 'from_name'] as $key) {
            if (!isset($mailConfig[$key]) || $mailConfig[$key] === '') {
                throw new RuntimeException("Missing mail configuration setting: {$key}. (Set BREVO_API_KEY for HTTPS delivery, or SMTP_* variables for SMTP delivery).");
            }
        }
    }

    // Mark due reminders for renewed/replaced documents as failed.
    // They must not be sent for documents that are no longer current.
    $staleStmt = $pdo->prepare("
        UPDATE reminder_logs AS rl
        INNER JOIN documents AS d ON d.id = rl.document_id
        SET rl.status = 'failed'
        WHERE rl.status = 'pending'
          AND d.is_current = 0
    ");
    $staleStmt->execute();

    $staleCount = $staleStmt->rowCount();

    // Fetch due reminders for current documents only.
    $stmt = $pdo->prepare("
        SELECT
            rl.id AS reminder_id,
            rl.document_id,
            rl.reminder_type,
            rl.scheduled_for,
            d.document_type,
            d.expiry_date,
            u.full_name,
            u.email
        FROM reminder_logs AS rl
        INNER JOIN documents AS d
            ON d.id = rl.document_id
        INNER JOIN users AS u
            ON u.id = d.user_id
        WHERE rl.status = 'pending'
          AND rl.scheduled_for <= CURDATE()
          AND d.is_current = 1
        ORDER BY rl.scheduled_for ASC, rl.id ASC
        LIMIT 50
    ");
    $stmt->execute();

    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sentCount = 0;
    $failedCount = 0;

    foreach ($reminders as $reminder) {
        $reminderId = (int) $reminder['reminder_id'];

        $messages = [
            'monthly'    => 'This is a reminder that your document has an upcoming expiry date.',
            'final_week' => 'Your document is scheduled to expire in 7 days.',
            'expiry_day' => 'Your document is scheduled to expire today.',
            'post_expiry' => 'Your document expired 7 days ago. Please check whether renewal is required.',
        ];

        $message = $messages[$reminder['reminder_type']]
            ?? 'Please check the expiry date of your document.';

        $expiryDate = date(
            'd M Y',
            strtotime($reminder['expiry_date'])
        );

        $name = $reminder['full_name'];
        $documentType = $reminder['document_type'];

        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeDocumentType = htmlspecialchars($documentType, ENT_QUOTES, 'UTF-8');
        $safeExpiryDate = htmlspecialchars($expiryDate, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        $htmlBody = "
            <div style=\"font-family:Arial,sans-serif;line-height:1.6;\">
                <h2>Document Expiry Reminder</h2>
                <p>Hello {$safeName},</p>
                <p>{$safeMessage}</p>
                <p><strong>Document:</strong> {$safeDocumentType}</p>
                <p><strong>Expiry date:</strong> {$safeExpiryDate}</p>
                <p>Please log in to Vehicle Document Manager to review your document.</p>
                <p>Regards,<br>Vehicle Document Manager</p>
            </div>
        ";

        $altBody =
            "Hello {$name},\n\n" .
            "{$message}\n" .
            "Document: {$documentType}\n" .
            "Expiry date: {$expiryDate}\n\n" .
            "Please log in to Vehicle Document Manager to review your document.\n\n" .
            "Regards,\nVehicle Document Manager";

        $subject = 'Document Expiry Reminder - Vehicle Document Manager';

        try {
            if ($brevoApiKey !== '') {
                // Production: Send via Brevo HTTPS REST API (Port 443)
                $ch = curl_init('https://api.brevo.com/v3/smtp/email');
                $payload = json_encode([
                    'sender' => [
                        'name' => $fromName,
                        'email' => $fromEmail,
                    ],
                    'to' => [
                        [
                            'email' => $reminder['email'],
                            'name' => $reminder['full_name'],
                        ],
                    ],
                    'subject' => $subject,
                    'htmlContent' => $htmlBody,
                    'textContent' => $altBody,
                ], JSON_UNESCAPED_SLASHES);

                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_HTTPHEADER => [
                        'api-key: ' . $brevoApiKey,
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ],
                    CURLOPT_POSTFIELDS => $payload,
                ]);

                $response = curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($response === false || $httpCode < 200 || $httpCode >= 300) {
                    throw new RuntimeException(sprintf(
                        'Brevo API error (HTTP %d): %s',
                        $httpCode,
                        $response !== false ? $response : $curlError
                    ));
                }
            } else {
                // Local Development: Fallback to PHPMailer SMTP
                $mail = new PHPMailer(true);

                $mail->isSMTP();
                $mail->Host = $mailConfig['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $mailConfig['username'];
                $mail->Password = $mailConfig['password'];
                $mail->Port = (int) $mailConfig['port'];
                $mail->CharSet = 'UTF-8';
                $mail->Timeout = 20;

                if (strtolower($mailConfig['encryption']) === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif (strtolower($mailConfig['encryption']) === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    throw new RuntimeException('Unsupported SMTP encryption setting.');
                }

                $mail->setFrom(
                    $mailConfig['from_email'],
                    $mailConfig['from_name']
                );

                $mail->addAddress(
                    $reminder['email'],
                    $reminder['full_name']
                );

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->AltBody = $altBody;

                $mail->send();
            }

            // Mark as sent only if the document is still current.
            $updateStmt = $pdo->prepare("
                UPDATE reminder_logs AS rl
                INNER JOIN documents AS d ON d.id = rl.document_id
                SET rl.status = 'sent',
                    rl.sent_at = CURRENT_TIMESTAMP
                WHERE rl.id = :reminder_id
                  AND rl.status = 'pending'
                  AND d.is_current = 1
            ");

            $updateStmt->execute([
                'reminder_id' => $reminderId,
            ]);

            if ($updateStmt->rowCount() === 1) {
                $sentCount++;
                echo "Sent reminder #{$reminderId} to {$reminder['email']}.\n";
            } else {
                // The email was accepted by SMTP, but the log could not
                // be confirmed as sent. Avoid claiming it was recorded.
                echo "Warning: email sent, but reminder #{$reminderId} was not marked as sent.\n";
            }

        } catch (Throwable $e) {
            // Do not print SMTP credentials or detailed mail errors.
            error_log(
                "Reminder #{$reminderId} failed: " . $e->getMessage()
            );

            $failedStmt = $pdo->prepare("
                UPDATE reminder_logs
                SET status = 'failed'
                WHERE id = :reminder_id
                  AND status = 'pending'
            ");

            $failedStmt->execute([
                'reminder_id' => $reminderId,
            ]);

            $failedCount++;
            echo "Failed to send reminder #{$reminderId}. Check the PHP error log.\n";
        }
    }

    echo "\nReminder sending completed.\n";
    echo "Due reminders checked: " . count($reminders) . "\n";
    echo "Emails sent and recorded: {$sentCount}\n";
    echo "Failed reminders: {$failedCount}\n";
    echo "Stale reminders skipped: {$staleCount}\n";

} catch (Throwable $e) {
    error_log('Reminder sender stopped: ' . $e->getMessage());
    echo "The reminder sender stopped because of a configuration or database error.\n";
    echo "Check the PHP error log for details.\n";
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}