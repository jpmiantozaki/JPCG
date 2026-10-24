<?php
declare(strict_types=1);
require_once __DIR__.'/../src/Stage0SessionPresenceAudit.php';

$checks=0;
function check(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { fwrite(STDERR,"FAIL $label\n"); exit(1); }
    $checks++;
    echo "PASS $label\n";
}
function safeString(string $data): string { return chr(strlen($data)).$data; }
function loginFrame(string $id = 'INPP.test@example.invalid', int $commandMain=1, int $commandSub=1): string {
    $payload = safeString($id).safeString('fake-password').safeString('1026');
    return pack('vVCC',8+strlen($payload),1,$commandMain,$commandSub).$payload;
}
function envelope(string $frame,string $stage='0',string $relay=''):string {
    return bin2hex($frame).'~'.$stage.'~'.$relay;
}
$fresh=CgmStage0SessionPresenceAudit::inspect(envelope(loginFrame()));
$reused=CgmStage0SessionPresenceAudit::inspect(envelope(loginFrame(),'0','RELAY-FROM-OLD-UI'));
$fb=CgmStage0SessionPresenceAudit::inspect(envelope(loginFrame('INFB.test@example.invalid')));
check($fresh['classification']==='STAGE0_NO_RELAY','fresh recognized');
check($reused['classification']==='STAGE0_EXISTING_RELAY_UNTRUSTED','nonempty historical shape recognized');
check($fresh['relay_session_present']===false,'fresh empty relay');
check($reused['relay_session_present']===true,'reused relay presence');
check($fresh['structurally_recognized']===true && $reused['structurally_recognized']===true,'both structurally recognized');
check($fresh['login_namespace']==='INPP' && $fb['login_namespace']==='INFB','namespaces detected without exposing IDs');
check($fresh['packet_bytes']===strlen(loginFrame()),'length annotated');
foreach ([$fresh,$reused,$fb] as $result) {
    check($result['authorized_provider_may_forward']===false,'never forward');
    check($result['relay_session_authenticated']===false,'never authenticate relay');
    check($result['official_identity_bound']===false,'never bind official identity');
    check($result['game_authenticated']===false,'never claim game authentication');
    check(!str_contains(json_encode($result),'fake-password') && !str_contains(json_encode($result),'RELAY-FROM-OLD-UI') && !str_contains(json_encode($result),'test@example.invalid'),'redacted output');
}
$invalidFixtures=[
    ''=>'empty envelope',
    'a~0~'=>'odd hex',
    'xxxx~0~'=>'nonhex',
    '1234~1~'=>'reject stage1',
    envelope(loginFrame(),'1','abc')=>'valid frame wrong stage',
    envelope(loginFrame()).'~extra'=>'extra segment',
    envelope(loginFrame(),'0',"bad\x00token")=>'token control rejected',
    envelope(loginFrame(),'0',str_repeat('x',4097))=>'oversized token',
    envelope(loginFrame('WRONG.test@example.invalid'))=>'unknown namespace',
    envelope(loginFrame('INPP.test@example.invalid',0,22))=>'wrong stage0 opcode',
    envelope(substr(loginFrame(),0,-1))=>'truncated original frame',
    envelope(loginFrame().'X')=>'trailing byte',
    envelope(pack('vVCC',8,1,1,1))=>'missing strings',
    envelope(loginFrame('INPP.a'))=>'minimal namespace only',
    envelope(loginFrame(),'00','')=>'stage spelling noncanonical',
];
foreach ($invalidFixtures as $raw=>$label) {
    $answer=CgmStage0SessionPresenceAudit::inspect($raw);
    $expected = $label==='minimal namespace only';
    // 'INPP.a' is a syntactically valid claim, not an authenticated identity.
    check(($answer['classification']!=='INVALID')===$expected,$label);
    check($answer['authorized_provider_may_forward']===false,'invalid or minimal cannot forward');
}
check(CgmStage0SessionPresenceAudit::inspect(str_repeat('x',32769))['classification']==='INVALID','oversized envelope rejected');
echo "ALL $checks/$checks PASS\n";
