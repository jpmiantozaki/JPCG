<?php
declare(strict_types=1);
require __DIR__.'/_common.php';

// CGM Stage 2A v1.1 diagnostic/membership probe.
// Trace events contain no account identifier. Membership probes accept only data1.
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
$ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
$event = trim((string)($_POST['stage2a_event'] ?? $_GET['stage2a_event'] ?? ''));

if ($event !== '') {
    $allowed = ['verify_entry_data_present','verify_entry_missing_data','decrypt_ok_login_empty','decrypt_failed'];
    if (!in_array($event, $allowed, true)) {
        respond(['ok'=>false,'state'=>'ERROR','error'=>'invalid_trace_event'], 400);
    }
    error_log('[CGM_STAGE2A_TRACE] event='.$event.' method='.$method.' ua='.preg_replace('/\\s+/', '_', $ua));
    respond(['ok'=>true,'state'=>'TRACE','event'=>$event]);
}

$login = value('data1');
if ($login === '') respond(['ok'=>false,'state'=>'ERROR','error'=>'missing_login_id'], 400);

$db = db();
$q = $db->prepare('SELECT MAX(vip_expiry) AS vip_expiry FROM codes WHERE login_id = ? AND redeemed_at IS NOT NULL');
$q->execute([$login]);
$row = $q->fetch(PDO::FETCH_ASSOC) ?: [];
$expiry = (int)($row['vip_expiry'] ?? 0);
$state = $expiry <= 0 ? 'INACTIVE' : ($expiry <= time() ? 'EXPIRED' : 'ACTIVE');
$hash = hash('sha256', $login);

error_log('[CGM_STAGE2A_PROBE_V11] method='.$method.' ua='.preg_replace('/\\s+/', '_', $ua).' login_hash='.$hash.' state='.$state.' vip_expiry='.$expiry);
respond([
    'ok'=>true,
    'state'=>$state,
    'login_id_hash'=>$hash,
    'vip_expiry'=>$expiry,
    'vip_expiry_iso'=>$expiry > 0 ? gmdate('c',$expiry) : null,
]);
