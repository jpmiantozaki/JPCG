<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$env = getenv('JPCG_ADMIN_KEY') ?: '';
$header = $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';
echo json_encode([
 'ok'=>true,
 'env_present'=>($env !== ''),
 'env_length'=>strlen($env),
 'header_present'=>($header !== ''),
 'header_length'=>strlen($header),
 'values_match'=>($env !== '' && $header !== '' && hash_equals($env,$header))
], JSON_UNESCAPED_SLASHES);
