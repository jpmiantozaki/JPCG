<?php
declare(strict_types=1);
require __DIR__ . '/SessionState.php';

function check(bool $yes, string $name): void {
    if (!$yes) throw new RuntimeException('FAIL ' . $name);
    echo 'PASS ' . $name . PHP_EOL;
}
function rejection(callable $fn): bool {
    try { $fn(); return false; } catch (LogicException $e) { return true; }
}
$now = 1700000000;
$active = ['state'=>'ACTIVE','vip_expiry'=>$now+1800];
$id = ['verified_by_official_provider'=>true,'subject'=>'offline-test-account'];

$s = new CgmStagingSessionState($now);
check($s->state() === 'NEW', 'new state');
check(rejection(fn()=>$s->beginProviderRequest()), 'out-of-order provider request rejected');
$s->confirmMembership(['state'=>'EXPIRED','vip_expiry'=>$now-1],$now);
check($s->state()==='DENIED', 'expired membership denied');
check(rejection(fn()=>$s->confirmIdentity($id)), 'denied state cannot verify identity');

$s = new CgmStagingSessionState($now);
$s->confirmMembership($active,$now);
check($s->state()==='MEMBERSHIP_CONFIRMED', 'active membership permits next stage');
$s->confirmIdentity(['subject'=>'unverified']);
check($s->state()==='DENIED', 'claimed identity denied');

$s = new CgmStagingSessionState($now);
$s->confirmMembership($active,$now);
$s->confirmIdentity($id);
check($s->state()==='IDENTITY_VERIFIED', 'verified identity permits next stage');
$s->beginProviderRequest();
check($s->state()==='PROVIDER_PENDING', 'provider request pending');
$s->acceptProviderResponse(['authorized'=>true,'session'=>'dummy-fixture','expires_at'=>$now+1800]);
check($s->state()==='DENIED', 'synthetic provider success denied');
check($s->snapshot($now)['session']===null, 'session never issued');
check(rejection(fn()=>$s->beginProviderRequest()), 'denied session cannot retry');

echo 'RESULT: 11 staging state tests passed' . PHP_EOL;
