<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$allowed = ['analytics.php','authenticate.php','redeemvip.php','session.php','verify.php'];

$path = trim((string)($_POST['path'] ?? ''));
$sourceMethod = strtoupper(trim((string)($_POST['source_method'] ?? 'UNKNOWN')));
$queryKeys = trim((string)($_POST['query_keys'] ?? ''));

if (!in_array($path, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid_path']);
    exit;
}

$safeMethod = preg_replace('/[^A-Z]/', '', $sourceMethod) ?: 'UNKNOWN';
$safeKeys = preg_replace('/[^A-Za-z0-9_,.-]/', '', $queryKeys);

error_log(
    '[CGM_INFINITYFREE_BRIDGE] path='.$path.
    ' source_method='.$safeMethod.
    ' query_keys='.($safeKeys !== '' ? $safeKeys : 'NONE')
);

echo json_encode(['ok'=>true,'state'=>'TRACE']);
