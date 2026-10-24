<?php
declare(strict_types=1);
require __DIR__ . '/MembershipProvenance.php';
require __DIR__ . '/IdentityBinding.php';

$checks = 0;
function check40g(bool $ok, string $name): void {
    global $checks;
    if (!$ok) throw new RuntimeException('FAIL ' . $name);
    $checks++;
    echo 'PASS ' . $name . PHP_EOL;
}

$now = 1700000000;
$account = 'test@example.invalid';
$prefix = 'INPP.' . $account;

/** @return callable(string):array<string,mixed> */
function lookup40g(array $expiryByLogin, int $now): callable {
    return static fn(string $login): array =>
        cgm_membership_payload((int)($expiryByLogin[$login] ?? 0), $now);
}

$lookup = lookup40g([$account => $now + 600], $now);
$r = cgm_staging_resolve_membership_provenance($lookup, $account, $now);
check40g($r['state'] === 'ACTIVE' && $r['lookup_login'] === $account
    && $r['match_type'] === 'EXACT', 'exact member tracked');

$lookup = lookup40g([$prefix => $now + 1200], $now);
$r = cgm_staging_resolve_membership_provenance($lookup, $account, $now);
check40g($r['state'] === 'ACTIVE' && $r['lookup_login'] === $prefix
    && $r['match_type'] === 'PREFIX_FALLBACK', 'prefixed fallback member tracked');

$lookup = lookup40g([$account => $now + 600, $prefix => $now + 1500], $now);
$r = cgm_staging_resolve_membership_provenance($lookup, $account, $now);
check40g($r['lookup_login'] === $prefix && $r['vip_expiry'] === $now + 1500,
    'latest active membership selected with provenance');

$lookup = lookup40g([$account => $now + 1500, $prefix => $now + 600], $now);
$r = cgm_staging_resolve_membership_provenance($lookup, $account, $now);
check40g($r['lookup_login'] === $account, 'longer exact active membership selected');

$lookup = lookup40g([$account => $now + 1500, $prefix => $now + 1500], $now);
$r = cgm_staging_resolve_membership_provenance($lookup, $account, $now);
check40g($r['lookup_login'] === $account, 'equal expiry preserves first candidate');

$lookup = lookup40g([$account => $now - 90, $prefix => $now - 30], $now);
$r = cgm_staging_resolve_membership_provenance($lookup, $account, $now);
check40g($r['state'] === 'EXPIRED' && $r['lookup_login'] === $prefix,
    'expired fallback provenance retained without granting access');

$lookup = lookup40g([], $now);
$r = cgm_staging_resolve_membership_provenance($lookup, $account, $now);
check40g($r['state'] === 'INACTIVE' && $r['lookup_login'] === null,
    'inactive has no selected account');

$lookup = lookup40g([$prefix => $now + 900], $now);
$r = cgm_staging_resolve_membership_provenance($lookup, $prefix, $now);
check40g($r['match_type'] === 'EXACT' && $r['lookup_login'] === $prefix,
    'already-prefixed input is exact only');

$calls = 0;
$bad = static function(string $login) use (&$calls, $now): array {
    $calls++;
    return cgm_membership_payload($now + 900, $now);
};
$r = cgm_staging_resolve_membership_provenance($bad, " bad\nlogin ", $now);
check40g($r['state'] === 'ERROR' && $calls === 0,
    'invalid login rejected before lookup');

$r = cgm_staging_resolve_membership_provenance(
    static function(string $login): array { throw new RuntimeException('private lookup error'); },
    $account, $now
);
check40g($r['state'] === 'ERROR' && $r['lookup_login'] === null,
    'lookup exception fails closed');

$r = cgm_staging_resolve_membership_provenance(
    static fn(string $login): array => [
        'scope'=>'CGM_MEMBERSHIP_ONLY','state'=>'ACTIVE',
        'vip_expiry'=>$now + 900,'membership_active'=>false
    ],
    $account, $now
);
check40g($r['state'] === 'ERROR', 'inconsistent membership denied');

$lookup = lookup40g([$account => $now + 100, $prefix => $now + 1200], $now);
$legacy = authfirst_resolve($account, $lookup);
$r = cgm_staging_resolve_membership_provenance($lookup, $account, $now);
check40g($legacy['state'] === $r['state']
    && $legacy['vip_expiry'] === $r['vip_expiry'],
    'matches deployed resolver state and expiry on fixture');

$pre = cgm_staging_precheck_with_provenance($lookup, $account, $now);
check40g($pre['state']->state() === 'MEMBERSHIP_CONFIRMED'
    && $pre['state']->membershipLogin() === $prefix,
    'chosen DB key carried into staging state');

$unbound = $pre['state'];
cgm_staging_bind_official_identity($unbound, 'offline-evidence', $now);
check40g($unbound->state() === 'DENIED',
    'provenance does not create a verified PlayPark identity');

$pre = cgm_staging_precheck_with_provenance(lookup40g([], $now), $account, $now);
check40g($pre['state']->state() === 'DENIED'
    && $pre['state']->membershipLogin() === null,
    'inactive lookup fails closed in session state');

$pre = cgm_staging_precheck_with_provenance($lookup, $account, $now);
check40g($pre['state']->snapshot($now)['authorized'] === false
    && $pre['state']->snapshot($now)['session'] === null,
    'active membership provenance never issues game session');

echo "RESULT: {$checks} Phase 40G provenance tests passed" . PHP_EOL;
