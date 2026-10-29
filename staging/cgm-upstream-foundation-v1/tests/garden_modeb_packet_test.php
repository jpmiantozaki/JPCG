<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/CgmGardenModeBPacket.php';
use CGM\Phase33X\CgmGardenModeBPacket;
$checks=0;
function ok(bool $cond,string $what):void{
    global $checks;
    if (!$cond)throw new RuntimeException('FAIL: '.$what);
    $checks++;
}
// Entirely synthetic tables (not taken from the game executable).
$bodyTable='';$headTable='';
for($i=0;$i<256;$i++)$bodyTable.=chr(($i*73+11)&255);
for($i=0;$i<512;$i++)$headTable.=chr(($i*31+97)&255);
$codec=new CgmGardenModeBPacket($bodyTable,$headTable);
function syntheticWire(string $data,int $headerSelector,int $bodySelector):string{
    global $bodyTable,$headTable;
    $originalLength=strlen($data)+2;
    $head=chr(($originalLength&255)^ord($headTable[$headerSelector*2]));
    $head.=chr((($originalLength>>8)&255)^ord($headTable[$headerSelector*2+1]));
    $head.=chr($headerSelector);
    $body=chr($bodySelector);
    for($i=0;$i<strlen($data);$i++){
        $index=$bodySelector+$i;
        // Host-only fixtures use a deterministic fallback for overflow.
        $lookup=$index<256 ? ord($bodyTable[$index]) : 0;
        $body.=chr(ord($data[$i]) ^ $lookup);
    }
    return $head.$body;
}
foreach([0,1,2,5,18,77,160,255] as $headerSelector){
    foreach([0,5,32,160,245] as $bodySelector){
        foreach([1,2,3,9,25,70,180] as $n){
            $plain=chr(0xc8);
            for($i=1;$i<$n;$i++)$plain.=chr(($i*7+15)&255);
            $wire=syntheticWire($plain,$headerSelector,$bodySelector);
            $result=$codec->decodeFrame($wire);
            ok($result['original_length']===$n+2,'exact original length');
            ok($result['wire_length']===$n+4,'exact original wire size');
            ok($result['decoded_prefix']===substr($plain,0,$result['decoded_prefix_bytes']), 'decoded prefix accurate');
            ok($result['complete']===($n+$bodySelector<=256), 'long body status correct');
            ok(($result['decoded_prefix'][0] ?? null)===chr(0xc8),'marker C8 reconstructed');
        }
    }
}
try { $codec->decodeFrame('short'); ok(false,'truncated'); }
catch(InvalidArgumentException $e){ok(true,'truncated refused');}
try { new CgmGardenModeBPacket('',str_repeat('Z',512)); ok(false,'bad tables'); }
catch(InvalidArgumentException $e){ok(true,'bad tables refused');}
echo "CGM_PHASE33X_PHP_GARDEN_PARSER_PASS assertions=$checks (SYNTHETIC ONLY)\n";
