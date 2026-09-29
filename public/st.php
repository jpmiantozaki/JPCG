<?php
// CGM v0.5A identity observer.
// Place as public/st.php beside the existing _common.php.
// It accepts ONLY the authenticated Login ID supplied by the APK's existing
// GardenManager -> RelayVerifyVIP path. It creates no VIP/relay session.
require __DIR__.'/_common.php';

$login = trim(value('data'));
if ($login === '') {
    respond(relay(true, 'CGM OBSERVER: ERROR', '', 0, '', 0), 200);
}

$status = 'INACTIVE';
$expiry = 0;
try {
    $db = db();
    $q = $db->prepare(
        'SELECT MAX(vip_expiry) AS vip_expiry FROM codes '
        .'WHERE login_id = ? AND redeemed_at IS NOT NULL'
    );
    $q->execute([$login]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    $expiry = (int)($row['vip_expiry'] ?? 0);

    if ($expiry > time()) {
        $status = 'ACTIVE';
    } elseif ($expiry > 0) {
        $status = 'EXPIRED';
    }

    error_log('[CGM_V05A_OBSERVER] login_hash=' . hash('sha256', $login)
        . ' status=' . $status . ' vip_expiry=' . $expiry);
} catch (Throwable $e) {
    $status = 'ERROR';
    $expiry = 0;
    error_log('[CGM_V05A_OBSERVER_ERROR] ' . $e->getMessage());
}

// Deliberately error=true, no data, no session. This endpoint observes status
// but cannot grant access or impersonate the legacy VIP service.
respond(relay(true, 'CGM OBSERVER: ' . $status, '', 0, '', $expiry), 200);
