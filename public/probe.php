<?php
declare(strict_types=1);
require __DIR__.'/_common.php';

// CGM v0.5B passive authenticated-identity observer.
// The APK sends only AUServerInfo.userInfo.szLoginID as data1.
// data2 belongs to the base APK's pre-existing analytics URL shape and is ignored.
// Never accept or log Audition password, PIN, session_key, ccukey, usersn, or Garden packets here.
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
error_log('[CGM_V05B_OBSERVER] login_hash='.$hash.' status='.$state.' membership_expiry='.$expiry);

respond([
    'ok' => true,
    'state' => $state,
    'login_id_hash' => $hash,
    'membership_expiry' => $expiry,
    'membership_expiry_iso' => $expiry > 0 ? gmdate('c', $expiry) : null,
]);
