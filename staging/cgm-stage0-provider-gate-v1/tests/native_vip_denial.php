<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/NativeVipDenialReply.php';
$passed=0;
function checkTest(string $name, bool $ok): void {
    global $passed;
    if (!$ok) { fwrite(STDERR, "FAIL $name\n"); exit(1); }
    $passed++; echo "PASS $name\n";
}
$encode = fn(string $s): string => 'FIXTURE|' . base64_encode($s);
$eligibleDenied = [
    'status' => 'CGM_MEMBERSHIP_NOT_ELIGIBLE', 'may_forward' => false,
    'claimed_login_eligible' => false, 'is_game_authenticated' => false,
    'is_official_identity_bound' => false,
];
$wire = CgmNativeVipDenialReply::encode($eligibleDenied, $encode);
checkTest('encrypted-denial-present', is_string($wire) && str_starts_with($wire, 'FIXTURE|'));
$payload = json_decode((string)base64_decode(substr((string)$wire, 8), true), true, 8, JSON_THROW_ON_ERROR);
checkTest('native-error-is-true', $payload['error'] === true);
checkTest('native-vip-marker-is-exact', $payload['message'] === 'VIP');
checkTest('no-fabricated-game-packet', $payload['data'] === '');
checkTest('no-fabricated-relay-session', $payload['session'] === '');
checkTest('no-fabricated-vip-expiry', $payload['vip_expiry'] === 0);
checkTest('response-uses-login-target-shape', $payload['servertype'] === 0);
checkTest('no-secret-keys-added', array_keys($payload) === ['error','message','data','servertype','session','vip_expiry']);
foreach (['PROVIDER_POSITIVE_SHAPE_UNVERIFIED','PROVIDER_REJECTION','INVALID_PACKET','DEPENDENCY_UNAVAILABLE'] as $status) {
    $other = $eligibleDenied; $other['status'] = $status;
    checkTest('no-vip-for-' . $status, CgmNativeVipDenialReply::encode($other, $encode) === null);
}
foreach (['may_forward'=>true,'claimed_login_eligible'=>true,'is_official_identity_bound'=>true,'is_game_authenticated'=>true] as $k => $v) {
    $other=$eligibleDenied; $other[$k]=$v;
    checkTest('contradictory-state-not-encoded-' . $k, CgmNativeVipDenialReply::encode($other,$encode)===null);
}
$other=$eligibleDenied;unset($other['claimed_login_eligible']);
checkTest('incomplete-metadata-not-encoded', CgmNativeVipDenialReply::encode($other,$encode)===null);
$threw=false;try {CgmNativeVipDenialReply::encode($eligibleDenied,fn($body)=>'');}catch(RuntimeException $e){$threw=true;}
checkTest('failed-encoder-is-not-silent', $threw);
checkTest('no-raw-credential-in-wire', !str_contains((string)$wire,'password')&&!str_contains((string)$wire,'member@example.test'));
echo "ALL $passed/$passed PASS\n";
