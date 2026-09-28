<?php
declare(strict_types=1);

// Temporary CGM diagnostic endpoint.
// It deliberately does NOT decrypt the payload and does NOT issue
// entitlement/relay/session credentials.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function diagnostic_log(array $record): void {
    error_log('[CGM_REDEEM_DIAGNOSTIC] '.json_encode($record, JSON_UNESCAPED_SLASHES));
}

$data = isset($_GET['data']) ? (string)$_GET['data'] : '';
$rawQuery = isset($_SERVER['QUERY_STRING']) ? (string)$_SERVER['QUERY_STRING'] : '';

$charset = 'empty';
if ($data !== '') {
    if (preg_match('/^[A-Fa-f0-9]+$/', $data)) {
        $charset = 'hex-like';
    } elseif (preg_match('/^[A-Za-z0-9+\/=_-]+$/', $data)) {
        $charset = 'base64-or-token-like';
    } elseif (preg_match('/^[\x20-\x7E]+$/', $data)) {
        $charset = 'printable-ascii';
    } else {
        $charset = 'other';
    }
}

$record = [
    'time_utc'          => gmdate('c'),
    'method'            => $_SERVER['REQUEST_METHOD'] ?? '',
    'path'              => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
    'query_length'      => strlen($rawQuery),
    'data_present'      => $data !== '',
    'data_length'       => strlen($data),
    'data_charset'      => $charset,
    // Hash lets us compare whether two payloads are identical without
    // writing the protected payload itself to the logs.
    'data_sha256'       => $data === '' ? null : hash('sha256', $data),
    'user_agent'        => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'content_type'      => $_SERVER['CONTENT_TYPE'] ?? '',
    'content_length'    => isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0,
];

diagnostic_log($record);

// Intentionally return a failure. This diagnostic endpoint must not create
// a session or make the APK believe VIP activation succeeded.
http_response_code(400);
echo json_encode([
    'error' => true,
    'message' => 'CGM diagnostic capture complete',
    'diagnostic' => [
        'data_present' => $record['data_present'],
        'data_length' => $record['data_length'],
        'data_charset' => $record['data_charset'],
        'data_sha256' => $record['data_sha256'],
    ],
], JSON_UNESCAPED_SLASHES);
