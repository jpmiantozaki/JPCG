<?php
declare(strict_types=1);
require __DIR__.'/_common.php';

// CGM Stage 2A membership probe.
// Receives only an already-authenticated login identifier as data1.
// It does not accept or process passwords, PINs, session_key, ccukey,
// official VIP tokens, or Garden packets.
$login = value('data1');

if ($login === '') {
    respond(['ok'=>false,'state'=>'ERROR','error'=>'missing_login_id'], 400);
}

$db = db();

$q = $db->prepare(
    'SELECT MAX(vip_expiry) AS vip_expiry
       FROM codes
      WHERE login_id = ?
        AND redeemed_at IS NOT NULL'
);
$q->execute([$login]);

$row = $q->fetch(PDO::FETCH_ASSOC) ?: [];
$expiry = (int)($row['vip_expiry'] ?? 0);

if ($expiry <= 0) {
    $state = 'INACTIVE';
} elseif ($expiry <= time()) {
    $state = 'EXPIRED';
} else {
    $state = 'ACTIVE';
}

$hash = hash('sha256', $login);
error_log(
    '[CGM_STAGE2A_PROBE] login_hash='.$hash.
    ' state='.$state.
    ' vip_expiry='.$expiry
);

respond([
    'ok' => true,
    'state' => $state,
    'login_id_hash' => $hash,
    'vip_expiry' => $expiry,
    'vip_expiry_iso' => $expiry > 0 ? gmdate('c', $expiry) : null,
]);
