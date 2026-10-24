<?php
declare(strict_types=1);

require_once __DIR__ . '/SessionState.php';
require_once __DIR__ . '/../../public/_cgm_membership.php';

/**
 * Offline-only eligibility adapter for the existing JPCG membership system.
 *
 * The lookup input is a *claimed* login identifier. Membership existence must
 * never be treated as official proof of account ownership, game authentication,
 * or PlayPark session authorization.
 *
 * The lookup callable is supplied by trusted server-side code, not HTTP input.
 * @param callable(string,int):array<string,mixed> $trustedLookup
 */
function cgm_staging_check_membership(
    callable $trustedLookup,
    string $claimedLogin,
    int $now
): CgmStagingSessionState {
    $state = new CgmStagingSessionState($now);
    $deny = static fn(): array => cgm_membership_payload(0, $now);
    $login = trim($claimedLogin);

    // Mirror the existing verify.php input boundary; preserve case, never
    // perform implicit INPP./plain-email alias fallback.
    if ($login === '' || strlen($login) > 320
        || preg_match('/[\x00-\x1F\x7F]/', $login)) {
        $state->confirmMembership($deny(), $now);
        return $state;
    }

    try {
        $membership = $trustedLookup($login, $now);
        if (!is_array($membership)
            || ($membership['scope'] ?? null) !== 'CGM_MEMBERSHIP_ONLY'
            || ($membership['membership_active'] ?? null) !== true) {
            $membership = $deny();
        }
    } catch (Throwable $e) {
        // Do not leak identifiers, credentials, PDO errors, or connection info.
        $membership = $deny();
    }

    $state->confirmMembership($membership, $now, $login);
    return $state;
}

/**
 * Reuses the production lookup function, but is NOT wired to any public route.
 * No PDO connection is created here. The caller must supply a server-owned
 * connection and must not expose this helper as a session-issuing endpoint.
 */
function cgm_staging_check_membership_pdo(
    PDO $pdo,
    string $claimedLogin,
    int $now
): CgmStagingSessionState {
    return cgm_staging_check_membership(
        static fn(string $login, int $timestamp): array =>
            cgm_membership_lookup($pdo, $login, $timestamp),
        $claimedLogin,
        $now
    );
}
