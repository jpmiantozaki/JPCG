<?php
declare(strict_types=1);
namespace CGM\Phase33Z;

/**
 * Metadata-only structural parser for original PC Garden command C8/02,
 * status=0. Source: native 0x00A6AAE9. Original archival captures:
 * 5357 bytes and 5622 bytes; all field lengths consumed, zero remainder.
 *
 * Does NOT resolve item meanings or provide game authentication.
 * Never returns actual packet values, credentials or record bytes.
 */
final class CgmC8Subtype02Structure
{
    private static function skip(string $p, int &$at, int $bytes): void {
        if ($bytes < 0 || $bytes > strlen($p) - $at)
            throw new \InvalidArgumentException('C8 subtype 02 truncated or invalid');
        $at += $bytes;
    }
    private static function u8(string $p, int &$at): int {
        if ($at >= strlen($p)) throw new \InvalidArgumentException('Missing byte');
        $v = ord($p[$at]); $at++; return $v;
    }
    private static function u16(string $p, int &$at): int {
        if ($at + 2 > strlen($p)) throw new \InvalidArgumentException('Missing u16');
        $v = unpack('v', substr($p, $at, 2))[1]; $at += 2; return $v;
    }
    private static function u32(string $p, int &$at): int {
        if ($at + 4 > strlen($p)) throw new \InvalidArgumentException('Missing u32');
        $v = unpack('V', substr($p, $at, 4))[1]; $at += 4; return $v;
    }
    private static function records(string $p, int &$at, int $count, int $width): int {
        if ($width < 1 || $count < 0 || $count > intdiv(strlen($p)-$at, $width))
            throw new \InvalidArgumentException('Record count exceeds packet remainder');
        $start=$at; self::skip($p, $at, $count*$width); return $start;
    }
    /** Count/offset metadata only. Original record meanings still unknown. */
    public static function inspect(string $packet): array {
        $length = strlen($packet);
        if ($length < 35 || $length > 65535 ||
            unpack('v', substr($packet, 0, 2))[1] !== $length ||
            ord($packet[2]) !== 0xC8 || ord($packet[3]) !== 0x02 || ord($packet[4]) !== 0)
            throw new \InvalidArgumentException('Expected full C8/02 success packet');
        $at=5;
        $headerAt=$at;
        // Five u32 and one u8 in native read order: u32,u32,u8,u32,u32,u32
        self::skip($packet, $at, 21);
        $pairArrays=[];
        for ($i=0; $i<3; ++$i) {
            $count=self::u32($packet, $at);
            $start=self::records($packet, $at, $count, 8);
            $pairArrays[]=['count'=>$count,'record_bytes'=>8,'first_offset'=>$start];
        }
        $groupCount=self::u8($packet, $at);
        $groups=[];
        for ($i=0; $i<$groupCount; ++$i) {
            $groupAt=$at;
            self::skip($packet, $at, 2);
            $n3=self::u8($packet, $at);
            $o3=self::records($packet, $at, $n3, 3);
            $n17=self::u8($packet, $at);
            $o17=self::records($packet, $at, $n17, 17);
            $n10=self::u8($packet, $at);
            $o10=self::records($packet, $at, $n10, 10);
            $groups[]=[
                'group_offset'=>$groupAt,
                'triples'=>['count'=>$n3,'width'=>3,'offset'=>$o3],
                'records17'=>['count'=>$n17,'width'=>17,'offset'=>$o17],
                'records10'=>['count'=>$n10,'width'=>10,'offset'=>$o10],
            ];
        }
        $n18=self::u16($packet,$at);
        $o18=self::records($packet,$at,$n18,18);
        $trailerAt=$at;
        self::skip($packet,$at,5);
        if($at!==$length)throw new \InvalidArgumentException('Unaccounted trailing bytes');
        return [
            'transport'=>'PC_GARDEN_MODE_B','command'=>'C8','subcommand'=>'02',
            'status_code'=>0,'packet_bytes'=>$length,
            'unknown_fixed_header'=>['offset'=>$headerAt,'bytes'=>21],
            'unknown_pair_arrays'=>$pairArrays,
            'outer_group_count'=>$groupCount,
            'unknown_nested_groups'=>$groups,
            'unknown_records18'=>['count'=>$n18,'width'=>18,'offset'=>$o18],
            'trailer'=>['offset'=>$trailerAt,'bytes'=>5],
            'unaccounted_bytes'=>0,
            'semantic_meanings_verified'=>false,
            'official_session_verified'=>false,
        ];
    }
}
