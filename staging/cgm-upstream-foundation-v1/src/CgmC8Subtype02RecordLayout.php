<?php
declare(strict_types=1);
namespace CGM\Phase33Z1;

require_once __DIR__ . '/CgmC8Subtype02Structure.php';
use CGM\Phase33Z\CgmC8Subtype02Structure;

/**
 * Read-only typed field decoder for original Windows Audition Garden C8/02,
 * sourced from 0x00A6AAE9 and checked against TWO original PCAP responses.
 *
 * These are transport-native field types and offsets, NOT official plant/item
 * semantic labels. This code does NOT authenticate, issue sessions, connect to
 * game services or overwrite any executable/client.
 */
final class CgmC8Subtype02RecordLayout
{
    private static function u8(string $p, int $offset): int {
        if ($offset < 0 || $offset >= strlen($p)) throw new \InvalidArgumentException('Invalid field boundary');
        return ord($p[$offset]);
    }
    private static function u32(string $p, int $offset): int {
        if ($offset < 0 || $offset + 4 > strlen($p)) throw new \InvalidArgumentException('Invalid field boundary');
        return unpack('V', substr($p, $offset, 4))[1];
    }
    private static function rows(string $p, int $offset, int $count, int $width, \Closure $parser): array {
        if ($count < 0 || $width < 1 || $offset < 0 || $count > intdiv(strlen($p) - $offset, $width))
            throw new \InvalidArgumentException('Invalid record window');
        $out=[];
        for($i=0;$i<$count;$i++) $out[]=$parser($p,$offset+$i*$width);
        return $out;
    }
    /**
     * Returns typed numeric packet records IN MEMORY. Values are not logged.
     * No field except byte indices/selectors/coordinate candidates is assigned
     * an inventory-specific meaning without live-state confirmation.
     */
    public static function decode(string $packet): array {
        $m=CgmC8Subtype02Structure::inspect($packet);
        $pairs=[];
        foreach($m['unknown_pair_arrays'] as $arr) {
            $pairs[]=self::rows($packet,$arr['first_offset'],$arr['count'],8,
                static fn(string $p,int $i):array=>[
                    'field0_u32'=>self::u32($p,$i),
                    'field4_u32'=>self::u32($p,$i+4),
                ]);
        }
        $groups=[];
        foreach($m['unknown_nested_groups'] as $g) {
            $triples=$g['triples'];
            $records17=$g['records17'];
            $records10=$g['records10'];
            $groups[]=[
                'triples'=>self::rows($packet,$triples['offset'],$triples['count'],3,
                    static fn(string $p,int $i):array=>[
                        'field0_u8'=>self::u8($p,$i),
                        'field1_u8'=>self::u8($p,$i+1),
                        'field2_u8'=>self::u8($p,$i+2),
                    ]),
                'records17'=>self::rows($packet,$records17['offset'],$records17['count'],17,
                    static fn(string $p,int $i):array=>[
                        'record_index'=>self::u8($p,$i),             // +0, native object/map key
                        'unknown_u32'=>self::u32($p,$i+1),          // +1..4
                        'group_selector'=>self::u8($p,$i+5),        // +5, native <2 array selection
                        'coordinate_candidate_x'=>self::u8($p,$i+6),
                        'coordinate_candidate_y'=>self::u8($p,$i+7),
                        'state8'=>[
                            self::u8($p,$i+8),self::u8($p,$i+9),self::u8($p,$i+10),
                            self::u8($p,$i+11),self::u8($p,$i+12),
                        ],
                        'unknown_tail_u32'=>self::u32($p,$i+13),    // +13..16
                    ]),
                'records10'=>self::rows($packet,$records10['offset'],$records10['count'],10,
                    static fn(string $p,int $i):array=>[
                        'record_index'=>self::u8($p,$i),
                        'unknown_u32'=>self::u32($p,$i+1),
                        'group_selector'=>self::u8($p,$i+5),
                        'field6_u8'=>self::u8($p,$i+6),
                        'field7_u8'=>self::u8($p,$i+7),
                        'field8_u8'=>self::u8($p,$i+8),
                        'field9_u8'=>self::u8($p,$i+9),
                    ]),
            ];
        }
        $r18=$m['unknown_records18'];
        $records18=self::rows($packet,$r18['offset'],$r18['count'],18,
            static fn(string $p,int $i):array=>[
                'record_index'=>self::u8($p,$i),               // +0
                'array_selector_u32'=>self::u32($p,$i+1),      // +1..4, native selects one of 2 arrays
                'unknown_a_u32'=>self::u32($p,$i+5),           // +5..8
                'unknown_b_u32'=>self::u32($p,$i+9),           // +9..12
                'class_u8'=>self::u8($p,$i+13),               // +13, native 0 / 1 / other tallies
                'unknown_c_u32'=>self::u32($p,$i+14),          // +14..17
            ]);
        return [
            'packet_bytes'=>$m['packet_bytes'],
            'command'=>'C8','subcommand'=>'02','status'=>0,
            'pairs8'=>$pairs,'groups'=>$groups,'records18'=>$records18,
            'semantics'=>'FIELD_LAYOUT_ONLY_NOT_INVENTORY',
            'authenticated_session_proven'=>false,
        ];
    }
    /**
     * Safe report of numeric shape only. No IDs, quantities, tokens or packet
     * content are returned. Duplicate indices are summarized, not hidden.
     */
    public static function summarize(string $packet): array {
        $d=self::decode($packet);
        $report=[
            'packet_bytes'=>$d['packet_bytes'],
            'pairs8_counts'=>array_map('count',$d['pairs8']),
            'groups'=>[], 'record18_groups'=>[],
            'status'=>'STRUCTURE_ONLY', 'authenticated_session_proven'=>false,
        ];
        foreach($d['groups'] as $g) {
            $pages=[];
            foreach($g['records17'] as $r) {
                $sel=$r['group_selector'];
                if (!isset($pages[$sel]))$pages[$sel]=['indices'=>[],'xy'=>[],'x'=>[],'y'=>[]];
                $pages[$sel]['indices'][]=$r['record_index'];
                $pair=$r['coordinate_candidate_x'].','.$r['coordinate_candidate_y'];
                $pages[$sel]['xy'][]=$pair;
                $pages[$sel]['x'][]=$r['coordinate_candidate_x'];
                $pages[$sel]['y'][]=$r['coordinate_candidate_y'];
            }
            ksort($pages);
            $pageSummary=[];
            foreach($pages as $selector=>$rows) {
                $n=count($rows['indices']);
                $ux=count(array_unique($rows['x']));
                $uy=count(array_unique($rows['y']));
                $uc=count(array_unique($rows['xy']));
                $pageSummary[]=[
                    'selector'=>(int)$selector,'count'=>$n,
                    'min_index'=>min($rows['indices']),'max_index'=>max($rows['indices']),
                    'unique_x'=>$ux,'unique_y'=>$uy,'unique_coordinate_pairs'=>$uc,
                    'complete_rectangular_grid'=>$uc===$n && $ux*$uy===$n,
                ];
            }
            $extraIndices=array_column($g['records10'],'record_index');
            $report['groups'][]=[
                'triples_count'=>count($g['triples']),
                'records17_count'=>count($g['records17']),
                'grid_groups'=>$pageSummary,
                'records10_count'=>count($g['records10']),
                'records10_min_index'=>$extraIndices ? min($extraIndices):null,
                'records10_max_index'=>$extraIndices ? max($extraIndices):null,
                'records10_unique_indices'=>count(array_unique($extraIndices)),
            ];
        }
        $byGroup=[];
        foreach($d['records18'] as $r) {
            $sel=$r['array_selector_u32'];
            if (!isset($byGroup[$sel]))$byGroup[$sel]=['indices'=>[],'classes'=>[]];
            $byGroup[$sel]['indices'][]=$r['record_index'];
            $byGroup[$sel]['classes'][]=$r['class_u8'];
        }
        ksort($byGroup);
        foreach($byGroup as $selector=>$r) {
            $classes=array_count_values($r['classes']);ksort($classes);
            $indexes=$r['indices'];
            $report['record18_groups'][]=[
                'selector'=>(int)$selector,'count'=>count($indexes),
                'min_index'=>min($indexes),'max_index'=>max($indexes),
                'unique_indices'=>count(array_unique($indexes)),
                'class_counts'=>$classes,
            ];
        }
        return $report;
    }
}
