<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
echo json_encode([
    'service' => 'cgm-upstream-foundation',
    'status' => 'STAGING_ONLY_PROVIDER_NOT_CONFIGURED',
    'officially_authenticated' => false,
    'game_session_issued' => false,
    'garden_authorized' => false,
], JSON_THROW_ON_ERROR);
