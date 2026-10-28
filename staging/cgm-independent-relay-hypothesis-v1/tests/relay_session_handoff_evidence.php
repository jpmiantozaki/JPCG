<?php
declare(strict_types=1);
require __DIR__.'/../src/RelaySessionHandoffEvidence.php';
$n=0;
function check(bool $condition, string $name): void {
    global $n;
    if (!$condition) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    $n++;
}
$cases=[
    ['fresh-new','', 'token-new', 'token-new',false,'FRESH_REPLY_HANDOFF_MATCH'],
    ['existing-same','token-old','token-old','token-old',false,'EXISTING_VALUE_HANDOFF_MATCH'],
    ['existing-changed','token-old','token-new','token-new',false,'CHANGED_VALUE_HANDOFF_MATCH'],
    ['prior-unknown',null,'token-new','token-new',false,'HANDOFF_MATCH_PRIOR_UNKNOWN'],
    ['response-mismatch','token-old','token-new','token-other',false,'RESPONSE_TO_STAGE1_MISMATCH'],
    ['no-status','','token-new','token-new',null,'RESPONSE_STATUS_UNOBSERVED'],
    ['denied','','token-new','token-new',true,'RESPONSE_ERROR_NO_HANDOFF'],
    ['missing-response','','',null,false,'RESPONSE_SESSION_ABSENT'],
    ['unobserved-response','','',null,null,'RESPONSE_STATUS_UNOBSERVED'],
    ['no-followup','','token-new',null,false,'STAGE1_REQUEST_UNOBSERVED'],
    ['empty-followup','','token-new','',false,'STAGE1_REQUEST_SESSION_ABSENT'],
    ['literal-backslash','','bad\nvalue','bad\nvalue',false,'FRESH_REPLY_HANDOFF_MATCH'],
    ['invalid-length','','token-new',str_repeat('X',4097),false,'INVALID_RELAY_WITNESS'],
];
foreach ($cases as [$label,$a,$b,$c,$err,$want]) {
    $res=CgmRelaySessionHandoffEvidence::assess($a,$b,$c,$err);
    check($res['classification']===$want,$label);
    foreach (['session_issuer_verified','relay_session_authenticated',
        'official_identity_bound','official_login_accepted','game_server_accepted',
        'garden_server_accepted','may_forward'] as $field) {
        check($res[$field]===false,$label.' '.$field);
    }
    $s=json_encode($res,JSON_THROW_ON_ERROR);
    check(!str_contains($s,'token-new') && !str_contains($s,'token-old') &&
        !str_contains($s,'token-other') && !str_contains($s,'bad\\nvalue'),$label.' sanitized');
}
$r=CgmRelaySessionHandoffEvidence::assess(null,null,'some-session',false);
check($r['classification']==='RESPONSE_SESSION_UNOBSERVED','archived response token unavailable');
check($r['stage0_response_present']===null,'missing is not empty');
$r=CgmRelaySessionHandoffEvidence::assess('old','new','new',false);
check($r['response_matches_stage1']===true && $r['response_matches_incoming']===false,
    'changed value is not authenticated rotation');
$r=CgmRelaySessionHandoffEvidence::assess('old','new','wrong',false);
check($r['response_matches_stage1']===false && $r['response_matches_incoming']===false,
    'response mismatch detected');
$r=CgmRelaySessionHandoffEvidence::assess('',"bad\nvalue",'x',false);
check($r['classification']==='INVALID_RELAY_WITNESS','control character rejected');
echo "ALL $n/$n PASS\n";
