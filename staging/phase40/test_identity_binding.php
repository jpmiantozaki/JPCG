<?php
declare(strict_types=1);
require __DIR__ . '/IdentityBinding.php';

$checks = 0;
function check40f(bool $ok, string $name): void {
    global $checks;
    if (!$ok) throw new RuntimeException('FAIL ' . $name);
    $checks++;
    echo 'PASS ' . $name . PHP_EOL;
}
class LocalFixtureVerifier implements CgmOfficialIdentityVerifier {
    public int $calls = 0;
    public function __construct(
        public ?array $result,
        public bool $throws = false
    ) {}
    public function verify(string $opaqueEvidence, int $now): ?array {
        $this->calls++;
        if ($this->throws) throw new RuntimeException('Offline fixture failure');
        return $this->result;
    }
}
function activeState(int $now, string $login = 'INPP.Test@Example.com'): CgmStagingSessionState {
    return cgm_staging_check_membership(
        static fn(string $key, int $t): array => cgm_membership_payload($t + 1800, $t),
        $login,
        $now
    );
}
$now = 1700000000;
$exact = new LocalFixtureVerifier([
    'login_id' => 'INPP.Test@Example.com',
    'subject' => 'fixture-official-subject'
]);

$s = activeState($now);
check40f($s->membershipLogin() === 'INPP.Test@Example.com', 'membership lookup key recorded');

cgm_staging_bind_official_identity($s, 'opaque-fixture', $now);
check40f($s->state() === 'DENIED', 'default verifier unavailable and denies');

$s = activeState($now);
cgm_staging_bind_official_identity($s, '{"verified_by_official_provider":true}', $now);
check40f($s->state() === 'DENIED', 'caller-provided verified flag is not proof');

$s = activeState($now);
cgm_staging_bind_official_identity($s, 'opaque-fixture', $now, $exact);
check40f($s->state() === 'IDENTITY_VERIFIED', 'exact matching offline verifier fixture accepted');
check40f($s->subject() === 'fixture-official-subject', 'fixture subject recorded');
check40f($s->snapshot($now)['authorized'] === false && $s->snapshot($now)['session'] === null,
    'fixture identity is not a game session');

$s = activeState($now);
cgm_staging_bind_official_identity($s, 'opaque-fixture', $now,
    new LocalFixtureVerifier(['login_id'=>'other@example.com','subject'=>'other']));
check40f($s->state() === 'DENIED', 'cross-account result rejected');

$s = activeState($now);
cgm_staging_bind_official_identity($s, 'opaque-fixture', $now,
    new LocalFixtureVerifier(['login_id'=>'INPP.test@Example.com','subject'=>'subject']));
check40f($s->state() === 'DENIED', 'case mismatch rejected');

$s = activeState($now);
cgm_staging_bind_official_identity($s, 'opaque-fixture', $now,
    new LocalFixtureVerifier(['login_id'=>'Test@Example.com','subject'=>'subject']));
check40f($s->state() === 'DENIED', 'prefix alias mismatch rejected');

$s = activeState($now);
cgm_staging_bind_official_identity($s, 'opaque-fixture', $now,
    new LocalFixtureVerifier(['login_id'=>'INPP.Test@Example.com','subject'=>'']));
check40f($s->state() === 'DENIED', 'empty verified subject rejected');

$s = activeState($now);
cgm_staging_bind_official_identity($s, 'opaque-fixture', $now,
    new LocalFixtureVerifier(null, true));
check40f($s->state() === 'DENIED', 'verifier exception denied');

$s = activeState($now);
$verifier = new LocalFixtureVerifier(['login_id'=>'INPP.Test@Example.com','subject'=>'subject']);
cgm_staging_bind_official_identity($s, '', $now, $verifier);
check40f($s->state() === 'DENIED' && $verifier->calls === 0,
    'missing evidence denied without verifier call');

$s = activeState($now);
$verifier = new LocalFixtureVerifier(['login_id'=>'INPP.Test@Example.com','subject'=>'subject']);
cgm_staging_bind_official_identity($s, str_repeat('x', 8193), $now, $verifier);
check40f($s->state() === 'DENIED' && $verifier->calls === 0,
    'oversized evidence denied without verifier call');

$s = new CgmStagingSessionState($now);
$s->confirmMembership(cgm_membership_payload($now + 1800, $now), $now);
cgm_staging_bind_official_identity($s, 'opaque-fixture', $now, $exact);
check40f($s->state() === 'DENIED', 'missing membership lookup key prevents identity binding');

$s = activeState($now);
$s->confirmIdentity([]);
$rejected = false;
try {
    cgm_staging_bind_official_identity($s, 'opaque-fixture', $now, $exact);
} catch (LogicException $e) {
    $rejected = true;
}
check40f($rejected, 'out-of-order identity binding rejected');

echo "RESULT: {$checks} Phase 40F identity binding tests passed" . PHP_EOL;
