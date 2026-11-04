<?php
declare(strict_types=1);

require_once __DIR__ . '/../../public/_secure_packet.php';
require_once __DIR__ . '/phase40z_stage0_preflight.php';

function fixture(string $email = 'synthetic@example.invalid', string $stage = '0',
                 string $prefix = 'INPP.', ?int $declaredSize = null): string
{
    $strings = [$prefix . $email, 'not-a-real-password', 'test-client-version'];
    $fields = '';
    foreach ($strings as $value) {
        $fields .= chr(strlen($value)) . $value;
    }
    $size = $declaredSize ?? (8 + strlen($fields));
    $packet = pack('vVCC', $size, 0, 1, 1) . $fields;
    return cgm_encrypt_packet(bin2hex($packet) . '~' . $stage . '~', 'abcdef', 0);
}

$tests = 0;
function check(bool $pass, string $name): void {
    global $tests;
    $tests++;
    if (!$pass) { throw new RuntimeException('FAIL ' . $name); }
}

$future = 2100000000;
$now = 2000000000;
$idSeen = null;
$yes = static function(string $id, int $timestamp) use (&$idSeen, $future): array {
    $idSeen = $id;
    return ['state' => 'ACTIVE', 'vip_expiry' => $future];
};
$no = static fn(string $id, int $timestamp): array =>
    ['state' => 'INACTIVE', 'vip_expiry' => 0];
$expired = static fn(string $id, int $timestamp): array =>
    ['state' => 'ACTIVE', 'vip_expiry' => $timestamp];
$error = static fn(string $id, int $timestamp): array =>
    throw new RuntimeException('fixture internal secret, must not leak');

$sample = fixture();
$allowed = cgm40z_stage0_preflight($sample, $yes, $now);
check($allowed === ['decision'=>'ACTIVE','forward_original'=>true], 'active');
check($idSeen === 'INPP.synthetic@example.invalid', 'unmodified identity');
check(!str_contains(json_encode($allowed), 'not-a-real-password'), 'no credentials in output');
check(cgm40z_stage0_preflight($sample, $no, $now)['forward_original'] === false, 'inactive');
check(cgm40z_stage0_preflight($sample, $expired, $now)['decision'] === 'INACTIVE', 'expired');
check(cgm40z_stage0_preflight($sample, $error, $now)['decision'] === 'ERROR', 'DB error');
check(cgm40z_stage0_preflight(fixture(stage:'1'), $yes, $now)['decision'] === 'ERROR', 'wrong stage');
check(cgm40z_stage0_preflight(fixture(prefix:'INFB.'), $yes, $now)['decision'] === 'ERROR', 'legacy login excluded');
check(cgm40z_stage0_preflight(fixture(declaredSize:1000), $yes, $now)['decision'] === 'ERROR', 'invalid packet length');
check(cgm40z_stage0_preflight(substr($sample, 0, -1) . 'x', $yes, $now)['decision'] === 'ERROR', 'malformed protection');
check(cgm40z_stage0_preflight('', $yes, $now)['decision'] === 'ERROR', 'empty payload');
check(cgm40z_stage0_preflight($sample, $yes, $future)['decision'] === 'INACTIVE', 'expiry equal now');
check(cgm40z_stage0_preflight(fixture(email:'not-an-email'), $yes, $now)['decision'] === 'ERROR', 'invalid email');
check(cgm40z_stage0_preflight($sample, fn() => ['state'=>'ACTIVE','vip_expiry'=>'2100000000'], $now)['decision'] === 'ERROR', 'malformed expiry');
check(cgm40z_stage0_preflight($sample, fn() => ['state'=>'ACTIVE','vip_expiry'=>$future], $now)['decision'] === 'ACTIVE', 'valid future expiry');

printf("PASS: %d synthetic stage-0 preflight checks. No live provider traffic.\n", $tests);
