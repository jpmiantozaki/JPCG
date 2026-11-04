<?php
declare(strict_types=1);

/**
 * Phase 40 provider boundary. Offline/staging only.
 *
 * This module is NOT connected to authenticate.php, session.php, or verify.php.
 * Membership eligibility is not official authentication or a session token.
 * No real provider is configured; the default always denies.
 */
interface CgmAuthorizedSessionProvider
{
    /** @param array<string,mixed> $verifiedIdentity */
    public function requestSession(array $verifiedIdentity): array;
}

final class CgmUnavailableSessionProvider implements CgmAuthorizedSessionProvider
{
    public function requestSession(array $verifiedIdentity): array
    {
        return [
            'authorized' => false,
            'state' => 'PROVIDER_UNAVAILABLE',
            'session' => null,
            'expires_at' => 0,
        ];
    }
}

/**
 * Pure decision function. Identity must originate from an official verified
 * authentication result, never from the user's claimed email or CGM status.
 *
 * @param array<string,mixed> $membership
 * @param array<string,mixed>|null $verifiedIdentity
 * @return array{authorized:bool,state:string,session:null,expires_at:int}
 */
function cgm_session_decision(
    array $membership,
    ?array $verifiedIdentity,
    ?CgmAuthorizedSessionProvider $provider = null,
    ?int $now = null
): array {
    $deny = static fn(string $reason): array => [
        'authorized' => false, 'state' => $reason,
        'session' => null, 'expires_at' => 0,
    ];
    $now ??= time();

    // Do not permit caller-provided membership status to authorize game access.
    if (($membership['state'] ?? null) !== 'ACTIVE'
        || !is_int($membership['vip_expiry'] ?? null)
        || $membership['vip_expiry'] <= $now) {
        return $deny('MEMBERSHIP_INACTIVE');
    }
    if ($verifiedIdentity === null
        || ($verifiedIdentity['verified_by_official_provider'] ?? null) !== true
        || !is_string($verifiedIdentity['subject'] ?? null)
        || trim($verifiedIdentity['subject']) === '') {
        return $deny('OFFICIAL_IDENTITY_REQUIRED');
    }

    // Only a future, independently vetted official provider may return
    // an authorized session. No active provider is implemented in this phase.
    $provider ??= new CgmUnavailableSessionProvider();
    $result = $provider->requestSession($verifiedIdentity);

    // Guard against unexpected provider responses. No unverified session
    // material is exposed by this staging-only decision boundary.
    if (($result['authorized'] ?? null) !== true) {
        return $deny('PROVIDER_UNAVAILABLE');
    }
    return $deny('PROVIDER_NOT_INTEGRATED');
}
