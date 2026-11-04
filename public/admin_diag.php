<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
// Legacy diagnostic retained for compatibility; never disclose key properties.
$expected = getenv('JPCG_ADMIN_KEY') ?: '';
$provided = (string)($_SERVER['HTTP_X_ADMIN_KEY'] ?? '');
if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'unauthorized']);
    exit;
}
echo json_encode(['ok'=>true,'diagnostic'=>'admin_authenticated']);
