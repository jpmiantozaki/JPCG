<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/CgmC8Subtype02Structure.php';
use CGM\Phase33Z\CgmC8Subtype02Structure as Parser;
$checks=0;
function ck(bool $ok,string $label):void {
    global $checks;
    if(!$ok)throw new RuntimeException('FAIL '.$label);
    ++$checks;
}
function sample(array $pairs,array $groups,int $n18):string {
    $p="\xc8\x02\x00".str_repeat("\0",21);
    foreach($pairs as $n)$p.=pack('V',$n).str_repeat("\0",$n*8);
    $p.=chr(count($groups));
    foreach($groups as [$n3,$n17,$n10]){
        $p.="\0\0".chr($n3).str_repeat("\0",$n3*3);
        $p.=chr($n17).str_repeat("\0",$n17*17);
        $p.=chr($n10).str_repeat("\0",$n10*10);
    }
    $p.=pack('v',$n18).str_repeat("\0",$n18*18).str_repeat("\0",5);
    return pack('v',strlen($p)+2).$p;
}
foreach([
    [5357,[6,27,4],[[0,80,5]],200],
    [5622,[4,30,7],[[3,80,22]],203],
] as [$length,$pairs,$groups,$n18]){
    $f=sample($pairs,$groups,$n18);
    ck(strlen($f)===$length,'capture-shaped SYNTHETIC length');
    $r=Parser::inspect($f);
    ck($r['packet_bytes']===$length,'length metadata');
    ck(array_column($r['unknown_pair_arrays'],'count')===$pairs,'three 8-byte arrays');
    ck($r['outer_group_count']===count($groups),'group count');
    ck($r['unknown_nested_groups'][0]['records17']['count']===80,'80 17-byte records');
    ck($r['unknown_records18']['count']===$n18,'18-byte records');
    ck($r['trailer']['offset']===$length-5,'trailer offset');
    ck($r['unaccounted_bytes']===0,'no trailing bytes');
    ck($r['semantic_meanings_verified']===false,'semantics not claimed');
    ck($r['official_session_verified']===false,'official session not claimed');
}
foreach([0,1,2,3] as $n)foreach([0,1,3] as $g){
    $f=sample([$n,$n+1,$n+2],array_fill(0,$g,[$n,$n,$n]),$n);
    $r=Parser::inspect($f);
    ck($r['packet_bytes']===strlen($f),'small length');
    ck($r['outer_group_count']===$g,'small groups');
    ck($r['unknown_records18']['count']===$n,'small records');
}
$good=sample([0,1,2],[[2,3,1]],4);
foreach([
    substr($good,0,-1),
    substr_replace($good,"\x00",0,1),
    substr_replace($good,"\xc9",2,1),
    substr_replace($good,"\x03",3,1),
    substr_replace($good,"\x01",4,1),
    $good."\0",
    str_repeat("\0",10),
] as $bad){
    try{Parser::inspect($bad);ck(false,'invalid rejection');}
    catch(InvalidArgumentException $e){ck(true,'invalid rejection');}
}
echo "CGM_PHASE33Z_C8_02_STRUCTURE_PASS assertions=$checks SYNTHETIC_ONLY\n";
