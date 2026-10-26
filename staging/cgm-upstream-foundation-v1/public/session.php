<?php
declare(strict_types=1);
/**
 * Safe placeholder: NEVER parses, logs or forwards the original credential-
 * bearing ?data= query. This is deliberately NOT a working legacy endpoint.
 * Do not point the actual game APK to this unconfigured staging route.
 */
http_response_code(503);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
echo json_encode([
    'scope' => 'CGM_UPSTREAM_STAGING_ONLY',
    'status' => 'NO_AUTHORIZED_PROVIDER_CONFIGURED',
    'officially_authenticated' => false,
    'game_session_issued' => false,
    'garden_authorized' => false,
], JSON_THROW_ON_ERROR);
