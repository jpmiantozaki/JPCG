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
    $pdo = db();
    $now = time();
    // Exact-match behavior remains the default. A claimed INPP. login ID
    // is not, by itself, proof of ownership of the corresponding plain ID.
    $aliasEnabled = getenv('CGM_VERIFY_ALLOW_PREFIX_ALIAS') === '1';
    if ($aliasEnabled) {
        // STAGING ONLY: alias-resolution must not be enabled in production
        // without verified identity binding and an explicit conflict policy.
        $lookup = static fn(string $candidate): array => cgm_membership_lookup($pdo, $candidate, $now);
        $candidates = [$login];
        if (str_starts_with($login, 'INPP.') && filter_var(substr($login, 5), FILTER_VALIDATE_EMAIL) !== false) {
            $candidates[] = substr($login, 5);
        } elseif (!str_starts_with($login, 'INPP.') && filter_var($login, FILTER_VALIDATE_EMAIL) !== false && strlen($login) <= 315) {
            $candidates[] = 'INPP.' . $login;
        }
        $payload = ['state' => 'INACTIVE', 'vip_expiry' => 0];
        foreach ($candidates as $candidate) {
            $candidateResult = $lookup($candidate);
            $state = (string)($candidateResult['state'] ?? 'ERROR');
            $expiry = (int)($candidateResult['vip_expiry'] ?? 0);
            if (!in_array($state, ['ACTIVE', 'EXPIRED', 'INACTIVE'], true)) {
                throw new RuntimeException('Unexpected lookup state');
            }
            if ($state === 'ACTIVE' && ($payload['state'] !== 'ACTIVE' || $expiry > $payload['vip_expiry'])) {
                $payload = ['state' => 'ACTIVE', 'vip_expiry' => $expiry];
            } elseif ($state === 'EXPIRED' && $payload['state'] !== 'ACTIVE' && $expiry > $payload['vip_expiry']) {
                $payload = ['state' => 'EXPIRED', 'vip_expiry' => $expiry];
            }
        }
        $payload = cgm_membership_payload((int)$payload['vip_expiry'], $now);
    } else {
        $payload = cgm_membership_lookup($pdo, $login, $now);
    }
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
