<?php
declare(strict_types=1);
namespace CGM\UpstreamV4;

require_once __DIR__ . '/ProviderHandoffGate.php';

use CGM\Phase40CD\CgmMembershipRepository;

/**
 * Server-owned adapter for the existing JPCG cgm_membership_lookup(PDO, id, now).
 * The verified account-link repository, never the client, chooses exact ID.
 * The current JPCG payload intentionally lacks the looked-up identity field;
 * this adapter attaches it ONLY after conducting the trusted exact lookup.
 */
final class ExactCgmMembershipRepository implements CgmMembershipRepository
{
    private \Closure $trustedLookup;

    /** @param callable(string,int):array $trustedLookup */
    public function __construct(callable $trustedLookup, private int $now)
    {
        $this->trustedLookup = \Closure::fromCallable($trustedLookup);
    }

    public function lookupExact(string $exactCgmLoginId): array
    {
        $failure = ['scope' => 'CGM_MEMBERSHIP_ONLY', 'state' => 'ERROR'];
        if ($this->now <= 0 || $exactCgmLoginId === '' ||
            strlen($exactCgmLoginId) > 320 || $exactCgmLoginId !== trim($exactCgmLoginId) ||
            preg_match('/[\x00-\x1F\x7F~]/', $exactCgmLoginId)) {
            return $failure;
        }
        try {
            $r = ($this->trustedLookup)($exactCgmLoginId, $this->now);
            if (!is_array($r) || ($r['scope'] ?? null) !== 'CGM_MEMBERSHIP_ONLY'
                || ($r['state'] ?? null) !== 'ACTIVE' ||
                ($r['membership_active'] ?? null) !== true ||
                !is_int($r['vip_expiry'] ?? null) || $r['vip_expiry'] <= $this->now) {
                return $failure;
            }
            return ['scope' => 'CGM_MEMBERSHIP_ONLY',
                'state' => 'ACTIVE',
                'vip_expiry' => $r['vip_expiry'],
                'membership_login_id' => $exactCgmLoginId,
            ];
        } catch (\Throwable $e) {
            return $failure;
        }
    }
}
