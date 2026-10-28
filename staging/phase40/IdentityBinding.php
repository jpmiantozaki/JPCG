<?php
declare(strict_types=1);

require_once __DIR__ . '/MembershipAdapter.php';

/**
 * Staging-only official identity verification boundary.
 *
 * No PlayPark verifier is available here. Implementations must be trusted
 * server-side adapters which validate evidence with the official provider.
 * The browser, APK, and CGM membership database are NOT identity verifiers.
 */
interface CgmOfficialIdentityVerifier
{
    /**
     * @return array{login_id:string,subject:string}|null
     * A result is meaningful ONLY from a trusted official verifier.
     */
    public function verify(string $opaqueEvidence, int $now): ?array;
}

final class CgmUnavailableOfficialIdentityVerifier implements CgmOfficialIdentityVerifier
{
    public function verify(string $opaqueEvidence, int $now): ?array
    {
        return null;
    }
}

/**
 * Bind the *exact CGM membership lookup identifier* to an official identity.
 * Neither caller-provided email nor a boolean "verified" assertion counts.
 *
 * This function never authorizes a real Garden session. In tests an injected
 * fixture verifier simulates a verified account result; this is NOT a real
 * official login integration.
 */
function cgm_staging_bind_official_identity(
    CgmStagingSessionState $state,
    string $opaqueEvidence,
    int $now,
    ?CgmOfficialIdentityVerifier $verifier = null
): void {
    if ($state->state() !== 'MEMBERSHIP_CONFIRMED') {
        throw new LogicException('Membership confirmation must precede identity binding');
    }

    $login = $state->membershipLogin();
    if ($login === null || $login === ''
        || strlen($login) > 320
        || $opaqueEvidence === '' || strlen($opaqueEvidence) > 8192) {
        $state->confirmIdentity([]);
        return;
    }

    // Default is deliberately unavailable: no real integration is configured.
    $verifier ??= new CgmUnavailableOfficialIdentityVerifier();
    try {
        $identity = $verifier->verify($opaqueEvidence, $now);
    } catch (Throwable $e) {
        $identity = null;
    }

    if (!is_array($identity)
        || !is_string($identity['login_id'] ?? null)
        || !is_string($identity['subject'] ?? null)
        || trim($identity['subject']) === ''
        || strlen($identity['subject']) > 320
        || !hash_equals($login, $identity['login_id'])) {
        // Case-preserved, no INPP. alias translation, and no cross-account use.
        $state->confirmIdentity([]);
        return;
    }

    $state->confirmIdentity([
        'verified_by_official_provider' => true,
        'subject' => $identity['subject'],
    ]);
}
