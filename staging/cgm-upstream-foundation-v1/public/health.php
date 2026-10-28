<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode([
  'service'=>'cgm-owned-upstream-staging-v4',
  'server_ready'=>true,
  'official_provider_configured'=>false,
  'independent_session_processor'=>false,
  'game_session_issued'=>false,
], JSON_THROW_ON_ERROR);
