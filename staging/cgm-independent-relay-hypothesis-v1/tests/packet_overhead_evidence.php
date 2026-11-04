<?php
declare(strict_types=1);
require_once __DIR__.'/../src/PacketOverheadEvidence.php';
$path = __DIR__.'/../fixtures/base_success_lengths_2026_10_08.json';
$fixture = json_decode((string)file_get_contents($path), true, 8, JSON_THROW_ON_ERROR);
$n=0;
function check(bool $pass,string $name):void{
    global $n;
    if(!$pass){fwrite(STDERR,"FAIL: $name\n");exit(1);}
    $n++;
    echo "PASS: $name\n";
}
$result=CgmPacketOverheadEvidence::assess($fixture);
check($result['sample_count']===7, 'all-seven-positive-BASE-pairs');
check($result['rejected_records']===0, 'no-record-rejected');
check($result['all_consistent_with_3_to_12']===true, 'all-in-observed-native-range');
check(array_column($result['observations'],'delta_bytes')===[5,5,12,6,12,11,7], 'exact-length-deltas');
check(array_column($result['observations'],'hypothetical_k')===[2,2,9,3,9,8,4], 'arithmetic-k-only');
check($result['distinct_deltas']===5,'variable-overhead-not-constant');
check($result['transform_algorithm_proven']===false,'not-proven-algorithm');
check($result['official_authentication_proven']===false,'no-game-auth-claim');
check($result['output_packets_reconstructable']===false,'no-packet-inference');
check(!str_contains(json_encode($result),'session_key'),'no-secret-session-content');
$bad=$fixture;$bad[0]['origin']='m.cabahug.xyz';
check(CgmPacketOverheadEvidence::assess($bad)['rejected_records']===1,'wrong-origin-not-attributed');
$bad=$fixture;$bad[0]['response_request_bytes']=57;
check(CgmPacketOverheadEvidence::assess($bad)['rejected_records']===1,'unpaired-size-rejected');
$bad=$fixture;$bad[0]['response_request_stage']=1;
check(CgmPacketOverheadEvidence::assess($bad)['rejected_records']===1,'unpaired-stage-rejected');
$bad=$fixture;$bad[0]['response_error']=true;
check(CgmPacketOverheadEvidence::assess($bad)['rejected_records']===1,'upstream-denial-not-classed-positive');
$bad=$fixture;$bad[0]['http_status']=502;
check(CgmPacketOverheadEvidence::assess($bad)['rejected_records']===1,'http-error-not-classed-positive');
$bad=$fixture;$bad[0]['raw_packet_hex']='DONT_LOG';
check(CgmPacketOverheadEvidence::assess($bad)['rejected_records']===1,'reject-unexpected-sensitive-fields');
$bad=$fixture;$bad[0]['response_bytes']=100;
check(CgmPacketOverheadEvidence::assess($bad)['all_consistent_with_3_to_12']===false,'out-of-range-delta-detected');
$bad=$fixture;$bad[0]['relay_session_bytes']=0;
check(CgmPacketOverheadEvidence::assess($bad)['rejected_records']===1,'invalid-continuity-shape-rejected');
check(CgmPacketOverheadEvidence::assess([])['all_consistent_with_3_to_12']===false,'empty-fixture-not-positive');
check(CgmPacketOverheadEvidence::assess([null,'abc'])['rejected_records']===2,'non-metadata-records-rejected');
echo "ALL $n/$n PASS\n";
