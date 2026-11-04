<?php
declare(strict_types=1);
require_once __DIR__.'/../src/Stage1CoverageInspector.php';
$n=0;
function check(bool $ok,string $label):void {global $n;if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);} $n++;echo "PASS $label\n";}
function packet(int $m,int $s,string $data=''):string {
    return pack('v',8+strlen($data)).pack('V',0x6c11).chr($m).chr($s).$data;
}
$cases=[
    ['PING',0,0,'','0x69B8C8'],
    ['VERIFY_CCU',0,22,pack('V',555).pack('V',666),'0x69A504'],
    ['GAME_LOGON',1,0,pack('V',555).pack('V',666),'0x69B9D8'],
    ['REQUEST_ENTER_CHANNEL',9,0,"\x04",'0x69BBA4'],
    ['REQUEST_USER_INFO',10,0,'','0x69BCC8'],
    ['REQUEST_COUPLE_LEVEL',31,12,"\x04Test",'0x69BDD8'],
];
foreach($cases as [$name,$m,$s,$data,$rva]) {
    $r=CgmStage1CoverageInspector::inspect(packet($m,$s,$data));
    check($r['kind']===$name,'recognize-'.$name);
    check($r['source_rva']===$rva,'rva-'.$name);
    check($r['game_authenticated']===false && $r['packet_accepted_by_server']===false,'unverified-'.$name);
    check($r['packet_bytes']===8+strlen($data),'size-'.$name);
}
check(CgmStage1CoverageInspector::inspect(packet(0,0,'A'))['kind']==='INVALID','reject-ping-extra-byte');
check(CgmStage1CoverageInspector::inspect(packet(9,0,''))['kind']==='INVALID','reject-missing-channel');
check(CgmStage1CoverageInspector::inspect(packet(9,0,'AB'))['kind']==='INVALID','reject-too-long-channel');
check(CgmStage1CoverageInspector::inspect(packet(10,0,'A'))['kind']==='INVALID','reject-user-info-extra-byte');
check(CgmStage1CoverageInspector::inspect(packet(0,22,'ABC'))['kind']==='INVALID','reject-truncated-ccu');
check(CgmStage1CoverageInspector::inspect(packet(1,0,str_repeat('A',9)))['kind']==='INVALID','reject-excess-logon');
check(CgmStage1CoverageInspector::inspect(packet(31,12,"\x05Test"))['kind']==='INVALID','reject-bad-couple-safe-length');
check(CgmStage1CoverageInspector::inspect(packet(31,12,"\x00"))['kind']==='INVALID','reject-empty-couple-string');
check(CgmStage1CoverageInspector::inspect(packet(31,12,"\x04TestX"))['kind']==='INVALID','reject-trailing-couple-data');
check(CgmStage1CoverageInspector::inspect(packet(255,1))['kind']==='UNRECOGNIZED','unknown-command-not-accepted');
check(CgmStage1CoverageInspector::inspect(substr(packet(0,0),0,1))['kind']==='INVALID','short-frame');
$x=packet(0,0);$x[0]="\x09";
check(CgmStage1CoverageInspector::inspect($x)['kind']==='INVALID','reject-header-length-mismatch');
check(CgmStage1CoverageInspector::inspect(str_repeat('X',8193))['kind']==='INVALID','reject-oversized-frame');
$r=CgmStage1CoverageInspector::inspect(packet(0,22,pack('V',12345).pack('V',67890)));
check(!str_contains(json_encode($r),'12345')&&!str_contains(json_encode($r),'67890'),'no-ccu-or-usersn-in-diagnostics');
$r=CgmStage1CoverageInspector::inspect(packet(31,12,"\x12PrivateNameExample"));
check($r['kind']==='REQUEST_COUPLE_LEVEL'&&!str_contains(json_encode($r),'PrivateNameExample'),'no-couple-name-in-diagnostics');
echo "ALL $n/$n PASS\n";
