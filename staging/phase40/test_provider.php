<?php
declare(strict_types=1);
require __DIR__ . '/AuthorizedSessionProvider.php';

function assertState(string $expected, array $actual): void {
    if (($actual['state'] ?? null) !== $expected
        || ($actual['authorized'] ?? null) !== false
        || ($actual['session'] ?? 'unexpected') !== null
        || ($actual['expires_at'] ?? -1) !== 0) {
        throw new RuntimeException('Expected denial ' . $expected . ', got: ' . json_encode($actual));
    }
    echo 'PASS ' . $expected . PHP_EOL;
}

$now = 1700000000;
$active = ['state' => 'ACTIVE', 'vip_expiry' => $now + 3600];
$identity = ['verified_by_official_provider' => true, 'subject' => 'official-subject-example'];

assertState('MEMBERSHIP_INACTIVE', cgm_session_decision(
    ['state' => 'INACTIVE', 'vip_expiry' => 0], $identity, null, $now
));
assertState('MEMBERSHIP_INACTIVE', cgm_session_decision(
    ['state' => 'ACTIVE', 'vip_expiry' => $now], $identity, null, $now
));
assertState('MEMBERSHIP_INACTIVE', cgm_session_decision(
    ['state' => 'ACTIVE', 'vip_expiry' => (string)($now + 3600)], $identity, null, $now
));
assertState('OFFICIAL_IDENTITY_REQUIRED', cgm_session_decision($active, null, null, $now));
assertState('OFFICIAL_IDENTITY_REQUIRED', cgm_session_decision(
    $active, ['subject' => 'claimed@example.invalid'], null, $now
));
assertState('PROVIDER_UNAVAILABLE', cgm_session_decision($active, $identity, null, $now));

// A mistaken or untrusted provider success is NOT a substitute for a vetted
// integration. The staging boundary is deliberately non-authorizing.
$untrusted = new class implements CgmAuthorizedSessionProvider {
    public function requestSession(array $verifiedIdentity): array {
        return ['authorized' => true, 'state' => 'ACTIVE',
                'session' => 'synthetic-placeholder', 'expires_at' => 1800000000];
    }
};
assertState('PROVIDER_NOT_INTEGRATED', cgm_session_decision($active, $identity, $untrusted, $now));
echo 'RESULT: 7 staging denial tests passed' . PHP_EOL;
