<?php
declare(strict_types=1);

/** Only the new authfirst API uses this adapter. Existing verify/redeem are unchanged. */
function authfirst_resolve(string $login, callable $lookup): array {
    // Stored CGM IDs include both plain email and the legacy INPP. prefix.
    // Do not change case, trim internally, or normalize Gmail addresses.
    $candidates = [$login];
    if (!str_starts_with($login, 'INPP.') && filter_var($login, FILTER_VALIDATE_EMAIL) !== false && strlen('INPP.' . $login) <= 320) {
        $candidates[] = 'INPP.' . $login;
    }
    $best = ['state' => 'INACTIVE', 'vip_expiry' => 0];
    foreach ($candidates as $candidate) {
        $result = $lookup($candidate);
        $state = (string)($result['state'] ?? 'ERROR');
        $expiry = (int)($result['vip_expiry'] ?? 0);
        if (!in_array($state, ['ACTIVE', 'EXPIRED', 'INACTIVE'], true)) {
            throw new RuntimeException('Unexpected CGM lookup status');
        }
        // Always examine both candidates; prefer an ACTIVE membership, then
        // the latest expired membership, otherwise INACTIVE.
        if ($state === 'ACTIVE' && ($best['state'] !== 'ACTIVE' || $expiry > $best['vip_expiry'])) {
            $best = ['state' => 'ACTIVE', 'vip_expiry' => $expiry];
        } elseif ($state === 'EXPIRED' && $best['state'] !== 'ACTIVE' && $expiry > $best['vip_expiry']) {
            $best = ['state' => 'EXPIRED', 'vip_expiry' => $expiry];
        }
    }
    return $best;
}
