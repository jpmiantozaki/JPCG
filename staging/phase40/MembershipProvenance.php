<?php
declare(strict_types=1);

require_once __DIR__ . '/MembershipAdapter.php';
require_once __DIR__ . '/../../public/_authfirst_resolve.php';

/**
 * Staging-only equivalent of authfirst_resolve() which retains the *actual*
 * CGM codes-table lookup key chosen by the existing resolver rules.
 *
 * THIS IS MEMBERSHIP PROVENANCE, NOT PROOF OF OFFICIAL ACCOUNT OWNERSHIP.
 * Do not route public authfirst.php to this file or issue game sessions.
 *
 * @param callable(string):array<string,mixed> $trustedLookup
 * @return array{state:string,vip_expiry:int,lookup_login:?string,match_type:?string,scope:string}
 */
function cgm_staging_resolve_membership_provenance(
    callable $trustedLookup,
    string $requestedLogin,
    int $now
): array {
    $deny = static fn(string $state): array => [
        'state' => $state, 'vip_expiry' => 0,
        'lookup_login' => null, 'match_type' => null,
        'scope' => 'CGM_MEMBERSHIP_ONLY',
    ];

    if ($requestedLogin !== trim($requestedLogin)
        || strlen($requestedLogin) < 1 || strlen($requestedLogin) > 320
        || preg_match('/[\x00-\x1F\x7F~]/', $requestedLogin)) {
        return $deny('ERROR');
    }

    $candidates = [$requestedLogin];
    // Match public/_authfirst_resolve.php's candidate ordering exactly.
    if (!str_starts_with($requestedLogin, 'INPP.')
        && filter_var($requestedLogin, FILTER_VALIDATE_EMAIL) !== false
        && strlen('INPP.' . $requestedLogin) <= 320) {
        $candidates[] = 'INPP.' . $requestedLogin;
    }

    $best = $deny('INACTIVE');
    try {
        foreach ($candidates as $candidate) {
            $result = $trustedLookup($candidate);
            if (!is_array($result)
                || ($result['scope'] ?? null) !== 'CGM_MEMBERSHIP_ONLY'
                || !is_int($result['vip_expiry'] ?? null)
                || !is_bool($result['membership_active'] ?? null)) {
                return $deny('ERROR');
            }

            $expiry = $result['vip_expiry'];
            $state = $result['state'] ?? null;
            // Reject internally inconsistent results instead of treating
            // client-supplied flags as trusted identity/membership evidence.
            if ($state !== cgm_membership_state($expiry, $now)
                || $result['membership_active'] !== ($state === 'ACTIVE')) {
                return $deny('ERROR');
            }

            if (($state === 'ACTIVE'
                && ($best['state'] !== 'ACTIVE' || $expiry > $best['vip_expiry']))
                || ($state === 'EXPIRED'
                    && $best['state'] !== 'ACTIVE'
                    && $expiry > $best['vip_expiry'])) {
                $best = [
                    'state' => $state,
                    'vip_expiry' => $expiry,
                    'lookup_login' => $candidate,
                    'match_type' => $candidate === $requestedLogin
                        ? 'EXACT' : 'PREFIX_FALLBACK',
                    'scope' => 'CGM_MEMBERSHIP_ONLY',
                ];
            }
        }
    } catch (Throwable $e) {
        return $deny('ERROR');
    }
    return $best;
}

/**
 * Pass ONLY the chosen membership database key to the state machine.
 * A PlayPark-verified identity, if eventually available, must independently
 * map to this key; do not assume email and INPP.email are the same account.
 *
 * @param callable(string):array<string,mixed> $trustedLookup
 * @return array{resolution:array<string,mixed>,state:CgmStagingSessionState}
 */
function cgm_staging_precheck_with_provenance(
    callable $trustedLookup,
    string $requestedLogin,
    int $now
): array {
    $resolution = cgm_staging_resolve_membership_provenance(
        $trustedLookup, $requestedLogin, $now
    );
    $session = new CgmStagingSessionState($now);
    $payload = cgm_membership_payload(
        $resolution['state'] === 'ACTIVE' ? $resolution['vip_expiry'] : 0,
        $now
    );
    $session->confirmMembership($payload, $now, $resolution['lookup_login']);
    return ['resolution' => $resolution, 'state' => $session];
}
