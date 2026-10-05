<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['ok' => true, 'stage' => 'cgm-render-session-relay-v1',
    'mode' => 'original-response-relay',
    'original_host' => parse_url(getenv('CGM_ORIGINAL_ORIGIN') ?: 'https://anlgarden.com', PHP_URL_HOST)],
    JSON_UNESCAPED_SLASHES);
