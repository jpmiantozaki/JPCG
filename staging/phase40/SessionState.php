<?php
declare(strict_types=1);

/**
 * Offline-only session state machine. No persistence, networking, or token
 * issuance. JPCG membership is a prerequisite, never official authentication.
 */
final class CgmStagingSessionState
{
    private const TRANSITIONS = [
        'NEW' => ['MEMBERSHIP_CONFIRMED', 'DENIED'],
        'MEMBERSHIP_CONFIRMED' => ['IDENTITY_VERIFIED', 'DENIED'],
        'IDENTITY_VERIFIED' => ['PROVIDER_PENDING', 'DENIED'],
        'PROVIDER_PENDING' => ['AUTHORIZED', 'DENIED'],
        'AUTHORIZED' => ['EXPIRED', 'REVOKED'],
        'EXPIRED' => [],
        'REVOKED' => [],
        'DENIED' => [],
    ];
    private string $state = 'NEW';
    private int $expiresAt = 0;
    private ?string $subject = null;
    private ?string $membershipLogin = null;
    private bool $providerIntegrated = false;

    public function __construct(private readonly int $createdAt) {}

    public function state(): string { return $this->state; }
    public function expiresAt(): int { return $this->expiresAt; }
    public function subject(): ?string { return $this->subject; }
    public function membershipLogin(): ?string { return $this->membershipLogin; }

    private function move(string $target): void
    {
        if (!in_array($target, self::TRANSITIONS[$this->state] ?? [], true)) {
            throw new LogicException('Invalid session transition: ' . $this->state . ' -> ' . $target);
        }
        $this->state = $target;
    }

    public function confirmMembership(array $membership, int $now, ?string $lookupLogin = null): void
    {
        if ($this->state !== 'NEW') throw new LogicException('Membership already checked');
        // Source must be a trusted membership lookup; external request fields
        // must never be passed here as if verified.
        if (($membership['state'] ?? null) !== 'ACTIVE'
            || !is_int($membership['vip_expiry'] ?? null)
            || $membership['vip_expiry'] <= $now) {
            $this->move('DENIED');
            return;
        }
        // Record the exact identifier used by the trusted CGM lookup.
        // This is not proof of PlayPark account ownership.
        $this->membershipLogin = $lookupLogin;
        $this->move('MEMBERSHIP_CONFIRMED');
    }

    public function confirmIdentity(array $identity): void
    {
        if ($this->state !== 'MEMBERSHIP_CONFIRMED') {
            throw new LogicException('Membership must be confirmed first');
        }
        if (($identity['verified_by_official_provider'] ?? null) !== true
            || !is_string($identity['subject'] ?? null)
            || trim($identity['subject']) === '') {
            $this->move('DENIED');
            return;
        }
        $this->subject = $identity['subject'];
        $this->move('IDENTITY_VERIFIED');
    }

    public function beginProviderRequest(): void
    {
        $this->move('PROVIDER_PENDING');
    }

    /** Real session authorization is intentionally unavailable in staging. */
    public function acceptProviderResponse(array $response): void
    {
        if ($this->state !== 'PROVIDER_PENDING') {
            throw new LogicException('No pending provider request');
        }
        // Never promote a fixture or caller-controlled response into a
        // PlayPark authorization. A future approved adapter must replace this.
        $this->move('DENIED');
    }

    public function snapshot(int $now): array
    {
        $state = $this->state;
        if ($state === 'AUTHORIZED' && $this->expiresAt <= $now) {
            $this->move('EXPIRED');
            $state = $this->state;
        }
        return [
            'state' => $state,
            'authorized' => false,
            'session' => null,
            'expires_at' => 0,
        ];
    }
}
