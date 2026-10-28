<?php
declare(strict_types=1);
namespace CGM\Phase40CD;

/**
 * Staging-only interface between independent, trusted provider verification
 * and CGM's exact server-side membership record. No HTTP, no game packet,
 * no PlayPark credential handling, no entitlement bypass, no session issuance.
 */
interface AuthorizedProviderVerifier
{
    /**
     * Must perform independent legitimate authentication/authorization checks.
     * Opaque proof is never parsed, logged or trusted directly by this policy.
     */
    public function verify(string $opaqueProof, int $now): ?VerifiedProviderSession;
}

/** Immutable attestation returned by a SERVER-SIDE authorized verifier, never client JSON. */
final readonly class VerifiedProviderSession
{
    public function __construct(
        public string $issuer,
        public string $subject,
        public string $verifiedEmail,
        public string $audience,
        public string $boundAttemptId,
        public int $attestedAt,
        public int $expiresAt,
        public bool $officialGameAuthenticated,
        public bool $officialGardenAuthorized,
        public bool $intermediaryEntitlementAccepted,
    ) {}
}

/** Authoritatively provisioned account link, not an email-derived alias. */
final readonly class VerifiedCgmLink
{
    public function __construct(
        public string $issuer,
        public string $subject,
        public string $verifiedEmail,
        public string $exactCgmLoginId,
        public int $validUntil,
        public bool $revoked,
    ) {}
}

interface VerifiedAccountLinkRepository
{
    public function get(string $issuer, string $subject): ?VerifiedCgmLink;
}

interface CgmMembershipRepository
{
    /** Must use EXACT linked CGM key, not a client-supplied ACTIVE flag. */
    public function lookupExact(string $exactCgmLoginId): array;
}

interface AtomicOneUseAttemptStore
{
    /**
     * True ONLY on first valid consumption. Real implementations must use
     * atomic, durable storage; this policy supplies no in-memory production store.
     */
    public function consume(string $issuer, string $subject, string $attemptId, int $validUntil): bool;
}

/** The only production-safe defaults without provider permissions/credentials. */
final class UnconfiguredProviderVerifier implements AuthorizedProviderVerifier
{
    public function verify(string $opaqueProof, int $now): ?VerifiedProviderSession { return null; }
}
final class UnconfiguredAccountLinkRepository implements VerifiedAccountLinkRepository
{
    public function get(string $issuer, string $subject): ?VerifiedCgmLink { return null; }
}
final class UnconfiguredMembershipRepository implements CgmMembershipRepository
{
    public function lookupExact(string $exactCgmLoginId): array { return ['state'=>'ERROR']; }
}
final class DenyOnlyAttemptStore implements AtomicOneUseAttemptStore
{
    public function consume(string $issuer, string $subject, string $attemptId, int $validUntil): bool { return false; }
}

final class ProviderHandoffGate
{
    /** The expected audience must come from trusted server configuration, not a request field. */
    public function __construct(
        private AuthorizedProviderVerifier $provider,
        private VerifiedAccountLinkRepository $links,
        private CgmMembershipRepository $membership,
        private AtomicOneUseAttemptStore $attempts,
        private string $expectedAudience,
    ) {}

    /** @return array{scope:string,decision:string,reason:string,session_issued:bool,game_packet_generated:bool,provider_session_created:bool} */
    private static function result(string $reason, bool $approved = false): array
    {
        return [
            'scope' => 'OFFLINE_PROVIDER_HANDOFF_POLICY_ONLY',
            'decision' => $approved ? 'PRECONDITIONS_ATTESTED_ONLY' : 'DENIED',
            'reason' => $reason,
            'session_issued' => false,
            'game_packet_generated' => false,
            'provider_session_created' => false,
        ];
    }

    /**
     * A successful policy decision only records that all injected trusted
     * providers attested preconditions. It does NOT authorize relay packet
     * generation, issue a session, or replace a PlayPark/ANLGarden decision.
     *
     * @return array{scope:string,decision:string,reason:string,session_issued:bool,game_packet_generated:bool,provider_session_created:bool}
     */
    public function evaluate(string $claimedEmail, string $opaqueProviderProof, string $attemptId, int $now): array
    {
        if ($now <= 0 || $claimedEmail === '' || strlen($claimedEmail) > 254
            || $claimedEmail !== trim($claimedEmail)
            || filter_var($claimedEmail, FILTER_VALIDATE_EMAIL) === false
            || $opaqueProviderProof === '' || strlen($opaqueProviderProof) > 4096
            || preg_match('/\A[A-Za-z0-9_-]{16,64}\z/D', $attemptId) !== 1
            || $this->expectedAudience === '' || strlen($this->expectedAudience) > 128) {
            return self::result('INVALID_REQUEST');
        }

        try {
            $attested = $this->provider->verify($opaqueProviderProof, $now);
            if (!$attested instanceof VerifiedProviderSession) return self::result('PROVIDER_UNCONFIGURED_OR_UNVERIFIED');
            if (preg_match('/\A[A-Za-z0-9._:-]{1,80}\z/D', $attested->issuer) !== 1
                || preg_match('/\A[A-Za-z0-9._:@-]{1,200}\z/D', $attested->subject) !== 1
                || $attested->verifiedEmail !== $claimedEmail
                || $attested->audience !== $this->expectedAudience
                || !hash_equals($attested->boundAttemptId, $attemptId)
                || $attested->attestedAt > $now
                || $attested->attestedAt < $now - 10
                || $attested->expiresAt <= $now) {
                return self::result('PROVIDER_ATTESTATION_NOT_BOUND');
            }
            if (!$attested->officialGameAuthenticated
                || !$attested->officialGardenAuthorized
                || !$attested->intermediaryEntitlementAccepted) {
                return self::result('PROVIDER_AUTHORIZATION_INCOMPLETE');
            }
            $link = $this->links->get($attested->issuer, $attested->subject);
            if (!$link instanceof VerifiedCgmLink
                || $link->revoked || $link->validUntil <= $now
                || $link->issuer !== $attested->issuer
                || $link->subject !== $attested->subject
                || $link->verifiedEmail !== $attested->verifiedEmail
                || $link->exactCgmLoginId === ''
                || strlen($link->exactCgmLoginId) > 320
                || preg_match('/[\x00-\x1F\x7F~]/', $link->exactCgmLoginId)) {
                return self::result('ACCOUNT_LINK_UNVERIFIED');
            }
            // No prefix stripping, email canonicalization, or highest-expiry
            // merging across unrelated membership records. The trusted link
            // alone chooses the exact key.
            $membership = $this->membership->lookupExact($link->exactCgmLoginId);
            if (($membership['scope'] ?? null) !== 'CGM_MEMBERSHIP_ONLY'
                || ($membership['state'] ?? null) !== 'ACTIVE'
                || ($membership['membership_login_id'] ?? null) !== $link->exactCgmLoginId
                || !is_int($membership['vip_expiry'] ?? null)
                || $membership['vip_expiry'] <= $now) {
                return self::result('CGM_MEMBERSHIP_NOT_ACTIVE');
            }
            // Store must provide an atomic server-side, one-use operation.
            // Effective expiry is bounded by the provider, link, and CGM TTL.
            $validUntil = min($attested->expiresAt, $link->validUntil, $membership['vip_expiry']);
            if (!$this->attempts->consume($attested->issuer, $attested->subject, $attemptId, $validUntil)) {
                return self::result('ATTEMPT_NOT_UNIQUE_OR_UNAVAILABLE');
            }
            return self::result('TRUSTED_PROVIDER_LINK_MEMBERSHIP_AND_ONE_USE_ATTESTED', true);
        } catch (\Throwable $error) {
            // Never include caught exception text, original proof, email,
            // exact membership key, session value, packet or token in output.
            return self::result('VERIFICATION_UNAVAILABLE');
        }
    }
}
