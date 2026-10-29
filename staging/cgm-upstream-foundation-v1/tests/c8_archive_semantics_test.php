<?php
declare(strict_types=1);
require_once __DIR__ . '/c8_record_layout_test.php'; // prior 3,087 synthetic regressions + fixture()
use CGM\Phase33Z1\CgmC8Subtype02RecordLayout as Parser;
use CGM\Phase33Z\CgmC8Subtype02Structure as Structure;
$checks=0;
function checked(bool $okay,string $why):void {global $checks;if (!$okay)throw new RuntimeException('FAILED '.$why);$checks++;}

foreach ([
  [[6,27,4],0,5,[80,120],false,5357],
  [[4,30,7],3,22,[123,80],true,5622],
] as [$counts,$n3,$extra,$groups18,$swap,$length]) {
    // All numeric values below are deliberately invented test fixtures.
    $packet = fixture($counts,$n3,$extra,$groups18,$swap);
    $state=pack('V',54321).pack('V',33).chr(2).pack('V',55).pack('V',77).pack('V',369);
    checked(strlen($state)===21,'header width');
    $packet=substr_replace($packet,$state,5,21);
    $metadata=Structure::inspect($packet);
    foreach($metadata['unknown_pair_arrays'] as $n=>$section) {
      $packet=substr_replace($packet,pack('VV',600+$n,12+$n),$section['first_offset'],8);
    }
    $offset=$metadata['unknown_nested_groups'][0]['records17']['offset'];
    $r17=chr(17).pack('V',11).chr($swap?1:0).chr(4).chr(5).
        chr(6).chr(7).chr(8).chr(9).chr(10).chr(11).chr(12).chr(13).chr(14);
    checked(strlen($r17)===17,'flower width');
    $packet=substr_replace($packet,$r17,$offset,17);
    $r=Parser::decode($packet);
    checked($r['packet_bytes']===$length,'packet length unchanged');
    checked($r['garden_state']=== [
      'garden_points'=>54321,'water_count'=>33,'water_tank_candidate'=>2,
      'fertilizer_count'=>55,'vitamin_count'=>77,'garden_background_id_candidate'=>369], 'scalar header semantics');
    checked($r['inventory']['seeds'][0]['item_id']===600,'seeds ID');
    checked($r['inventory']['accessories'][0]['item_id']===601,'accessory ID');
    checked($r['inventory']['management'][0]['item_id']===602,'management ID');
    checked($r['inventory']['seeds'][0]['quantity']===12,'item quantity');
    checked($r['pairs8'][0][0]['field0_u32']===$r['inventory']['seeds'][0]['item_id'],'old item ID alias');
    checked($r['pairs8'][0][0]['field4_u32']===$r['inventory']['seeds'][0]['quantity'],'old quantity alias');
    $f=$r['groups'][0]['records17'][0];
    checked($f['flower_instance_id']===17,'flower instance ID');
    checked($f['seed_catalog_id']===11,'seed catalog ID');
    checked($f['garden_page']===($swap?1:0),'page selector');
    checked($f['x']===4&&$f['y']===5,'flower XY');
    checked($f['water_state']===6&&$f['clean_state']===7,'water/clean');
    checked($f['fertilizer_state']===8&&$f['growth_level']===9&&$f['growth_points']===10,'fertilizer/growth');
    checked($f['vitamin_state']===11&&$f['soil_state']===12,'vitamin/soil byte offsets');
    checked($f['unmapped_movement_byte']===13&&$f['moving_state_candidate']===14,'unresolved 15/16 distinct bytes');
    checked($f['unknown_tail_u32']===unpack('V',"\x0b\x0c\x0d\x0e")[1],'deprecated tail raw alias unchanged');
    $g=$r['records18'][0];
    checked($g['position_axis_1_candidate']===$g['unknown_a_u32'],'18 position axis 1');
    checked($g['position_axis_2_candidate']===$g['unknown_b_u32'],'18 position axis 2');
    checked($g['status_code']===$g['class_u8'],'18 status code vs class');
    checked($g['auxiliary_value_unmapped']===$g['unknown_c_u32'],'18 auxiliary value');
    checked($r['harvest_drops_status']==='NOT_DECODED','harvest never inferred');
    checked($r['secret_garden_status']==='NOT_SUPPLIED','SG never fabricated');
    checked($r['authenticated_session_proven']===false,'no authentication claim');
    $summary=Parser::summarize($packet);
    checked($summary['inventory_counts']===['seeds'=>$counts[0],'accessories'=>$counts[1],'management'=>$counts[2]],'summary inventory counts');
    checked(!str_contains(json_encode($summary,JSON_THROW_ON_ERROR),'54321'),'summary does not leak numeric scalar');
}
echo "CGM_PHASE33Z3_ARCHIVE_MAPPING_PASS assertions=$checks (SYNTHETIC ONLY)\n";
