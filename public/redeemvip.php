<?php
require __DIR__ . '/_common.php';

$code = strtoupper(trim(request_value('code')));
$login = trim(request_value('login'));

if (!preg_match('/^JPANL[0-9]{9}$/', $code)) {
    respond(relay_response(true, 'Invalid JPANL test code', '', 0, '', 0), 400);
}
if ($login === '') {
    respond(relay_response(true, 'Missing test login identifier', '', 0, '', 0), 400);
}

$store = cleanup(load_store());
$session = token();
$relayData = token(18);
$vipExpiry = time() + VIP_DAYS * 86400;

$store[$session] = [
    'login' => $login,
    'code' => $code,
    'relay_data' => $relayData,
    'stage' => 'login',
    'vip_expiry' => $vipExpiry,
    'session_expires' => time() + SESSION_TTL
];
save_store($store);

error_log('[JPCG_MOCK_REDEEM] login_hash=' . hash('sha256', $login) .
          ' code=' . $code . ' session=' . substr($session, 0, 8) . '...');

respond(relay_response(false, '', $relayData, 0, $session, $vipExpiry));
