<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/CgmC8Subtype02RecordLayout.php';
use CGM\Phase33Z1\CgmC8Subtype02RecordLayout as Parser;
$assertions=0;
function ck(bool $success,string $label):void {
    global $assertions;
    if(!$success)throw new RuntimeException('FAIL '.$label);
    ++$assertions;
}
function fixture(array $pairCounts,int $triples,int $extras,array $selector18Counts,bool $swap17): string {
    $s="\xc8\x02\x00".str_repeat("\0",21);
    foreach($pairCounts as $section=>$n) {
        $s.=pack('V',$n);
        for($i=0;$i<$n;$i++)$s.=pack('VV',1000+$section*100+$i,$i+1);
    }
    $s.=chr(1); // one outer group
    $s.="\0\0".chr($triples);
    for($i=0;$i<$triples;$i++)$s.=chr($i)."\x03\x01";
    $s.=chr(80);
    for($i=0;$i<80;$i++) {
        $slot=$i;
        $selector=($i>=40?1:0)^($swap17?1:0);
        $col=2+2*($i%10);
        $row=5+2*(intdiv($i,10)%4);
        $s.=chr($slot).pack('V',264).chr($selector).chr($col).chr($row);
        $s.=chr(5).chr(5).chr(5).chr($swap17?3:4).chr(7+($i>=40?1:0));
        $s.=pack('V',0);
    }
    $s.=chr($extras);
    for($i=0;$i<$extras;$i++) {
        $s.=chr(80+$i).pack('V',333+$i).chr($i%2).chr(2+2*($i%10))."\x03\0\0";
    }
    $n18=array_sum($selector18Counts);$s.=pack('v',$n18);
    foreach($selector18Counts as $selector=>$n){
        for($i=0;$i<$n;$i++) {
            $s.=chr($i).pack('V',$selector).pack('V',10000+$i);
            $s.=pack('V',20000+$i).chr($i%3).pack('V',30000+$i);
        }
    }
    $s.=str_repeat("\0",5);
    return pack('v',strlen($s)+2).$s;
}
foreach([
    [[6,27,4],0,5,[80,120],false,5357],
    [[4,30,7],3,22,[123,80],true,5622],
] as [$pairCounts,$triples,$extras,$slotCounts,$swap,$len]){
    $packet=fixture($pairCounts,$triples,$extras,$slotCounts,$swap);
    ck(strlen($packet)===$len,'fixture matches original response shape');
    $d=Parser::decode($packet);$m=Parser::summarize($packet);
    ck($d['packet_bytes']===$len,'packet length');
    ck($m['pairs8_counts']===$pairCounts,'pair counts');
    ck(count($d['groups'])===1,'one group');
    ck(count($d['groups'][0]['records17'])===80,'80 grids');
    ck(count($d['groups'][0]['records10'])===$extras,'extras count');
    ck(count($d['records18'])===array_sum($slotCounts),'slots18 count');
    ck($m['groups'][0]['records10_min_index']===80,'extras index first');
    ck($m['groups'][0]['records10_max_index']===79+$extras,'extras index last');
    ck($m['groups'][0]['records10_unique_indices']===$extras,'extras no duplicates');
    ck(count($m['groups'][0]['grid_groups'])===2,'two grid selectors');
    foreach($m['groups'][0]['grid_groups'] as $g){
        ck($g['count']===40,'40 per group');
        ck($g['unique_x']===10 && $g['unique_y']===4,'10 by 4 grid');
        ck($g['unique_coordinate_pairs']===40,'grid coordinates unique');
        ck($g['complete_rectangular_grid']===true,'complete rectangle');
    }
    foreach($d['groups'][0]['records17'] as $i=>$r){
        ck($r['record_index']===$i,'17 native index');
        ck($r['group_selector']===(($i>=40?1:0)^($swap?1:0)),'17 native group');
        ck($r['unknown_u32']===264,'17 u32 field');
        ck($r['coordinate_candidate_x']===2+2*($i%10),'17 coordinate candidate x');
        ck($r['coordinate_candidate_y']===5+2*(intdiv($i,10)%4),'17 coordinate candidate y');
        ck($r['state8'][3]===($swap?3:4),'17 state8');
        ck($r['unknown_tail_u32']===0,'17 tail');
    }
    foreach($d['groups'][0]['records10'] as $i=>$r){
        ck($r['record_index']===80+$i,'10 native index');
        ck($r['unknown_u32']===333+$i,'10 u32 field');
        ck($r['group_selector']===$i%2,'10 group');
        ck($r['field6_u8']===2+2*($i%10),'10 field6');
        ck($r['field7_u8']===3,'10 field7');
    }
    foreach($m['record18_groups'] as $i=>$r){
        ck($r['selector']===$i,'18 native array selector');
        ck($r['count']===$slotCounts[$i],'18 selector count');
        ck($r['unique_indices']===$slotCounts[$i],'18 unique indices');
        ck($r['min_index']===0 && $r['max_index']===$slotCounts[$i]-1,'18 contiguous indices');
    }
    foreach($d['records18'] as $r){
        ck($r['unknown_a_u32']===10000+$r['record_index'],'18 field a');
        ck($r['unknown_b_u32']===20000+$r['record_index'],'18 field b');
        ck($r['unknown_c_u32']===30000+$r['record_index'],'18 field c');
        ck($r['class_u8']===$r['record_index']%3,'18 classification');
    }
    foreach($d['pairs8'] as $section=>$rows)foreach($rows as $i=>$r){
        ck($r['field0_u32']===1000+$section*100+$i,'8 field 0');
        ck($r['field4_u32']===$i+1,'8 field 4');
    }
    ck($d['semantics']==='FIELD_LAYOUT_ONLY_NOT_INVENTORY','no inventory claim');
    ck($d['authenticated_session_proven']===false,'no session claim');
}
$valid=fixture([2,3,1],2,2,[4,5],false);
$badPackets=[substr($valid,0,-1),$valid."\0",
    substr_replace($valid,"\x00",0,1),substr_replace($valid,"\xc9",2,1),
    substr_replace($valid,"\x03",3,1),substr_replace($valid,"\x01",4,1)];
foreach($badPackets as $bad){
    try { Parser::decode($bad); ck(false,'bad packet should fail'); }
    catch(InvalidArgumentException $ex){ck(true,'bad packet rejected');}
}
echo "CGM_PHASE33Z1_RECORD_LAYOUT_PASS assertions=$assertions SYNTHETIC_ONLY\n";
