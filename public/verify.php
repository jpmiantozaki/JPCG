<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_secure_packet.php';
require_once __DIR__ . '/_cgm_membership.php';

// Accepts the legacy APK's data=<protected login ID> wire format.
// Packet checksums provide compatibility, NOT authenticated proof of identity.
// This returns CGM membership state; it does not issue a game login/session.
try {
    $protected = value('data');
    if ($protected === '' || strlen($protected) > 4096) {
        throw new InvalidArgumentException('Missing or oversized protected data');
    }
    $login = trim(cgm_decrypt_packet($protected));
    if ($login === '' || strlen($login) > 320 || preg_match('/[\x00-\x1F\x7F]/', $login)) {
        throw new InvalidArgumentException('Invalid login identifier');
    }
} catch (Throwable $e) {
    error_log('[CGM_VERIFY] state=ERROR reason=invalid_request');
    cgm_secure_respond(cgm_membership_error('Invalid verification request.'), 200);
}

try {
    $payload = cgm_membership_lookup(db(), $login, time());
    error_log(sprintf(
        '[CGM_VERIFY] login_hash=%s state=%s expiry=%d scope=CGM_MEMBERSHIP_ONLY',
        hash('sha256', $login), $payload['state'], $payload['vip_expiry']
    ));
    // Legacy APK callbacks parse logical errors only for HTTP-success bodies.
    cgm_secure_respond($payload, 200);
} catch (Throwable $e) {
    // Do not log packets, database connection strings, SQL or account IDs.
    error_log('[CGM_VERIFY] state=ERROR reason=backend_failure');
    cgm_secure_respond(cgm_membership_error('Membership verification is unavailable.'), 200);
}
