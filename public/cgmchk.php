<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_secure_packet.php';

// Stage 2D diagnostic only.
// It checks whether the authenticated login ID already has a redeemed CGM
// entitlement. It deliberately returns error=false in every logical state so
// this diagnostic cannot recursively re-enter ReceiveNoVIPData().

$state = 'ERROR';
$expiry = 0;
$loginHash = '';

try {
    $protected = value('data');
    if ($protected === '') {
        throw new RuntimeException('missing protected data');
    }

    $login = trim(cgm_decrypt_packet($protected));
    if ($login === '' || strlen($login) > 320) {
        throw new RuntimeException('invalid login id');
    }

    $loginHash = hash('sha256', $login);
    $q = db()->prepare('SELECT MAX(vip_expiry) AS vip_expiry FROM codes WHERE login_id = ? AND redeemed_at IS NOT NULL');
    $q->execute([$login]);
    $row = $q->fetch();
    $expiry = (int)($row['vip_expiry'] ?? 0);

    if ($expiry <= 0) {
        $state = 'INACTIVE';
    } elseif ($expiry <= time()) {
        $state = 'EXPIRED';
    } else {
        $state = 'ACTIVE';
    }
} catch (Throwable $e) {
    error_log('[CGM_STAGE2D_SHADOW_ERROR] ' . get_class($e));
}

error_log(sprintf(
    '[CGM_STAGE2D_SHADOW] login_hash=%s state=%s expiry=%d',
    $loginHash !== '' ? $loginHash : 'unavailable',
    $state,
    $expiry
));

error_log(sprintf(
    '[CGM_STAGE2D_RESPONSE] login_hash=%s state=%s expiry=%d',
    $loginHash !== '' ? $loginHash : 'unavailable',
    $state,
    $expiry
));

// Empty message + error=false makes the existing RelayVerifyVIP callback return
// without changing the original non-VIP result already being shown by the APK.
cgm_secure_respond(relay(false, '', '', 0, '', $expiry), 200);
