<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
// Informational only; not a game login or session health check.
echo json_encode([
    'service' => 'CGM membership backend',
    'scope' => 'INFORMATION_ONLY',
    'health' => '/health.php',
    'membership' => '/authfirst.php',
    'redemption' => '/redeemvip.php',
    'game_session_provider' => 'NOT_VERIFIED'
], JSON_UNESCAPED_SLASHES);
