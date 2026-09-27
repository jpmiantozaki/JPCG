<?php
require __DIR__ . '/_common.php';

$session = trim(request_value('session'));
$store = cleanup(load_store());
save_store($store);

if ($session === '' || !isset($store[$session])) {
    respond(['ok' => false, 'state' => 'missing_or_expired'], 404);
}
$s = $store[$session];
respond([
    'ok' => true,
    'stage' => $s['stage'],
    'servertype' => $s['stage'] === 'game' ? 1 : 0,
    'vip_expiry' => $s['vip_expiry'],
    'session_expires' => $s['session_expires']
]);
