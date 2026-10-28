<?php
declare(strict_types=1);

require_once __DIR__ . '/../../public/_secure_packet.php';
require_once __DIR__ . '/phase40aa_synthetic_router.php';

$checks = 0;
function assertcase(bool $okay, string $label): void {
    global $checks;
    $checks++;
    if (!$okay) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}
function fakeLogin(string $id = 'INPP.synthetic@example.invalid', string $stage = '0'): string {
    $frameFields = '';
    foreach ([$id, 'fixture-password-not-real', 'fixture-v1026'] as $field) {
        $frameFields .= chr(strlen($field)) . $field;
    }
    $frame = pack('vVCC', 8 + strlen($frameFields), 0, 1, 1) . $frameFields;
    return cgm_encrypt_packet(bin2hex($frame) . '~' . $stage . '~', 'abcdef', 1);
}

putenv('CGM_PHASE40AA_SYNTHETIC_ONLY=1');
$now = 2000000000;
$request = fakeLogin();
$called = 0;
$received = null;
$active = static fn(string $id, int $time): array => ['state'=>'ACTIVE','vip_expiry'=>$time + 86400];
$inactive = static fn(string $id, int $time): array => ['state'=>'INACTIVE','vip_expiry'=>0];
$expired = static fn(string $id, int $time): array => ['state'=>'ACTIVE','vip_expiry'=>$time];
$badDb = static fn(string $id, int $time): array => throw new RuntimeException('private fixture database connection details');
$vipBody = cgm_encrypt_packet('{"error":true,"message":"VIP","data":"","session":""}', 'abcdef', 2);
$vip = ['status'=>200, 'headers'=>['content-type'=>'text/plain'], 'body'=>$vipBody];
$mockVip = static function(string $r) use (&$called, &$received, $vip): array {
    $called++;
    $received = $r;
    return $vip;
};

$result = cgm40aa_route_synthetic($request, $active, $mockVip, $now);
assertcase($result['route'] === 'DOWNSTREAM', 'active allows original downstream callback');
assertcase($called === 1, 'exactly one downstream invocation');
assertcase($received === $request, 'original opaque request is byte-identical');
assertcase($result['provider_response'] === $vip, 'provider VIP rejection preserved, no rewriting');
assertcase(cgm_decrypt_packet($result['provider_response']['body']) === '{"error":true,"message":"VIP","data":"","session":""}', 'logical upstream VIP remains rejected');
assertcase(!str_contains(json_encode(['route'=>$result['route']]), 'password'), 'decision never includes password');

$result = cgm40aa_route_synthetic($request, $inactive, $mockVip, $now);
assertcase($result === ['route'=>'BLOCKED','reason'=>'CGM_INACTIVE'], 'inactive blocks');
assertcase($called === 1, 'inactive does not reach provider');
$result = cgm40aa_route_synthetic($request, $expired, $mockVip, $now);
assertcase($result['reason'] === 'CGM_INACTIVE', 'expired blocks');
assertcase($called === 1, 'expired does not reach provider');
$result = cgm40aa_route_synthetic($request, $badDb, $mockVip, $now);
assertcase($result['reason'] === 'CGM_CHECK_ERROR', 'membership backend failure blocks');
assertcase($called === 1, 'DB failure does not reach provider');
$result = cgm40aa_route_synthetic(fakeLogin('INPP.real@example.com'), $active, $mockVip, $now);
assertcase($result['reason'] === 'NON_FIXTURE_ID', 'real or non-test identity blocked');
assertcase($called === 1, 'real identity cannot reach provider');
$result = cgm40aa_route_synthetic(fakeLogin(stage:'1'), $active, $mockVip, $now);
assertcase($result['reason'] === 'INVALID_FRAME', 'wrong stage rejected');
$result = cgm40aa_route_synthetic('garbage', $active, $mockVip, $now);
assertcase($result['reason'] === 'INVALID_FRAME', 'corrupt request rejected');
assertcase($called === 1, 'invalid inputs never reach provider');
$badMock = static fn(string $r): array => ['status'=>200,'body'=>'oops'];
$result = cgm40aa_route_synthetic($request, $active, $badMock, $now);
assertcase($result['reason'] === 'DOWNSTREAM_ERROR', 'invalid downstream response blocks');
$throwMock = static fn(string $r): array => throw new RuntimeException('secret simulated upstream failure');
$result = cgm40aa_route_synthetic($request, $active, $throwMock, $now);
assertcase($result['reason'] === 'DOWNSTREAM_ERROR', 'downstream exception blocks without leaking details');
assertcase(!str_contains(json_encode($result), 'secret'), 'internal failure secret not emitted');
$acceptedFixture = ['status'=>200, 'headers'=>['content-type'=>'application/octet-stream'], 'body'=>"MOCK_ONLY\0NO_REAL_SESSION"];
$result = cgm40aa_route_synthetic($request, $active, static fn(string $r): array => $acceptedFixture, $now);
assertcase($result['provider_response'] === $acceptedFixture, 'opaque mock success body is unchanged (not real auth)');
putenv('CGM_PHASE40AA_SYNTHETIC_ONLY=0');
$result = cgm40aa_route_synthetic($request, $active, $mockVip, $now);
assertcase($result['reason'] === 'TEST_MODE_DISABLED', 'default-off guard');
assertcase($called === 1, 'disabled test guard prevents downstream');

printf("PASS: %d synthetic route tests; no network, provider credentials, or real session.\n", $checks);
