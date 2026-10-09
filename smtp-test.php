<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$host = 'smtp-relay.brevo.com';

echo "=== SMTP CONNECTIVITY TEST ===\n\n";

// 1. DNS Resolution
echo "1. DNS Resolution for {$host}:\n";
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "   [FAIL] Could not resolve hostname.\n\n";
} else {
    echo "   [SUCCESS] Resolved IP: {$ip}\n\n";
}

// 2. TCP Port 587 Test
echo "2. TCP connection to {$host}:587 (Timeout: 5s):\n";
$errno = 0;
$errstr = '';
$t0 = microtime(true);
$fp587 = @fsockopen($host, 587, $errno, $errstr, 5.0);
$elapsed587 = round((microtime(true) - $t0) * 1000, 2);

if (is_resource($fp587)) {
    $banner = trim((string) fgets($fp587, 512));
    fclose($fp587);
    echo "   [SUCCESS] Connected in {$elapsed587}ms\n";
    echo "   Server Banner: {$banner}\n\n";
} else {
    echo "   [FAIL] Could not connect (Error #{$errno}: {$errstr}) in {$elapsed587}ms\n\n";
}

// 3. TCP Port 465 Test (Direct SSL/TLS)
echo "3. SSL connection to ssl://{$host}:465 (Timeout: 5s):\n";
$errno = 0;
$errstr = '';
$t0 = microtime(true);
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ]
]);
$fp465 = @stream_socket_client("ssl://{$host}:465", $errno, $errstr, 5.0, STREAM_CLIENT_CONNECT, $context);
$elapsed465 = round((microtime(true) - $t0) * 1000, 2);

if (is_resource($fp465)) {
    $banner = trim((string) fgets($fp465, 512));
    fclose($fp465);
    echo "   [SUCCESS] Connected in {$elapsed465}ms\n";
    echo "   Server Banner: {$banner}\n\n";
} else {
    echo "   [FAIL] Could not connect (Error #{$errno}: {$errstr}) in {$elapsed465}ms\n\n";
}

echo "=== TEST COMPLETED ===\n";
