<?php
require __DIR__ . '/_common.php';

$session = trim(request_value('session'));
$data = trim(request_value('data'));

$store = cleanup(load_store());
if ($session === '' || !isset($store[$session])) {
    save_store($store);
    respond(relay_response(true, 'Invalid or expired mock session', '', 0, '', 0), 401);
}

$s = $store[$session];
if (!hash_equals((string)$s['relay_data'], $data)) {
    respond(relay_response(true, 'Mock relay data mismatch', '', 0, '', 0), 401);
}

if (($s['vip_expiry'] ?? 0) <= time()) {
    unset($store[$session]);
    save_store($store);
    respond(relay_response(true, 'Mock VIP expired', '', 0, '', 0), 403);
}

/* Advance our independent mock protocol from LoginServer(0) to GameServer(1). */
$nextData = token(18);
$store[$session]['relay_data'] = $nextData;
$store[$session]['stage'] = 'game';
$store[$session]['session_expires'] = time() + SESSION_TTL;
save_store($store);

error_log('[JPCG_MOCK_VERIFY] session=' . substr($session, 0, 8) . '... stage=game');

respond(relay_response(false, '', $nextData, 1, $session, (int)$s['vip_expiry']));
