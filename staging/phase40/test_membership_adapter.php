<?php
declare(strict_types=1);
require __DIR__ . '/MembershipAdapter.php';

function check40e(bool $ok, string $name): void {
    if (!$ok) throw new RuntimeException('FAIL ' . $name);
    echo 'PASS ' . $name . PHP_EOL;
}
$now = 1700000000;
$captured = [];
$lookup = static function (string $login, int $timestamp) use (&$captured, $now): array {
    $captured[] = [$login, $timestamp];
    return cgm_membership_payload($now + 1800, $timestamp);
};
$s = cgm_staging_check_membership($lookup, '  INPP.Test@Example.com  ', $now);
check40e($s->state() === 'MEMBERSHIP_CONFIRMED', 'active membership looked up');
check40e($captured === [['INPP.Test@Example.com', $now]], 'case-preserving exact key without alias');
check40e($s->subject() === null, 'membership does not assert official identity');

$s = cgm_staging_check_membership(
    static fn(string $login, int $t): array => cgm_membership_payload(0, $t),
    'someone@example.invalid', $now
);
check40e($s->state() === 'DENIED', 'missing membership denied');

$s = cgm_staging_check_membership(
    static fn(string $login, int $t): array => cgm_membership_payload($t, $t),
    'someone@example.invalid', $now
);
check40e($s->state() === 'DENIED', 'expired membership denied');

$s = cgm_staging_check_membership(
    static fn(string $login, int $t): array => [
        'state'=>'ACTIVE', 'membership_active'=>true, 'vip_expiry'=>$t + 1800,
        'scope'=>'OFFICIAL_SESSION'
    ],
    'someone@example.invalid', $now
);
check40e($s->state() === 'DENIED', 'wrong membership scope denied');

$s = cgm_staging_check_membership(
    static fn(string $login, int $t): array => [
        'state'=>'ACTIVE', 'membership_active'=>false, 'vip_expiry'=>$t + 1800,
        'scope'=>'CGM_MEMBERSHIP_ONLY'
    ],
    'someone@example.invalid', $now
);
check40e($s->state() === 'DENIED', 'inconsistent active flag denied');

$calls = 0;
$s = cgm_staging_check_membership(
    static function(string $login, int $t) use (&$calls): array {
        $calls++;
        return cgm_membership_payload($t + 1800, $t);
    },
    "bad\nidentifier", $now
);
check40e($s->state() === 'DENIED' && $calls === 0, 'control-character input never queried');

$s = cgm_staging_check_membership(
    static function(string $login, int $t): array {
        throw new RuntimeException('simulated private DB failure');
    },
    'someone@example.invalid', $now
);
check40e($s->state() === 'DENIED', 'database failure denied');

$s = cgm_staging_check_membership($lookup, 'person@example.invalid', $now);
$rejected = false;
try { $s->beginProviderRequest(); } catch (LogicException $e) { $rejected = true; }
check40e($rejected, 'membership alone cannot start provider request');

echo 'RESULT: 10 staging membership adapter tests passed' . PHP_EOL;
