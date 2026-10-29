<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/CgmGardenModeBPacket.php';
use CGM\Phase33X\CgmGardenModeBPacket;

$checks = 0;
function ck(bool $value, string $message): void {
    global $checks;
    if (!$value) throw new RuntimeException('Test failure: ' . $message);
    $checks++;
}
// Test-only tables, NEVER derived from original game binary.
$bodyTable = '';
$headerTable = '';
for ($i=0;$i<256;$i++) $bodyTable .= chr(($i*73+11)&255);
for ($i=0;$i<512;$i++) $headerTable .= chr(($i*31+97)&255);
$decoder = new CgmGardenModeBPacket($bodyTable, $headerTable);

function fixture(string $data, int $headerSelector, int $bodySelector): string {
    global $bodyTable,$headerTable;
    $length = strlen($data)+2;
    if ($length>65535)throw new InvalidArgumentException('fixture too long');
    $wire = chr(($length&255)^ord($headerTable[$headerSelector*2]));
    $wire .= chr((($length>>8)&255)^ord($headerTable[$headerSelector*2+1]));
    $wire .= chr($headerSelector).chr($bodySelector);
    $key=ord($bodyTable[$bodySelector]);
    for ($i=0;$i<strlen($data);$i++) $wire.=chr(ord($data[$i])^$key);
    return $wire;
}
// Check ALL 256 selectors and explicit long packets; some exceed 256 bytes.
foreach([0,1,2,7,31,120,255] as $h) {
    foreach (range(0,255) as $selector) {
        foreach ([1,4,31] as $n) {
            $payload=chr(0xc8);
            for($i=1;$i<$n;$i++)$payload.=chr(($i*13+7)&255);
            $result=$decoder->decodeFrame(fixture($payload,$h,$selector));
            ck($result['status']==='FULLY_DECODED','fully decoded');
            ck($result['decoded_payload']===$payload,'original bytes');
            ck($result['decoded_prefix']===$payload,'legacy compatible field');
            ck($result['payload_bytes']===$n,'payload byte count');
            ck($result['complete']===true,'complete flag');
        }
    }
}
foreach([239,277,345,530,5357,5622] as $originalLength){
    $n=$originalLength-2;
    $payload=chr(0xc8);
    for($i=1;$i<$n;$i++)$payload.=chr(($i*17+31)&255);
    foreach([0,1,80,254,255] as $selector){
        $r=$decoder->decodeFrame(fixture($payload,207,$selector));
        ck($r['original_length']===$originalLength,'long original length');
        ck($r['complete'] && $r['decoded_payload']===$payload,'long full payload');
    }
}
$good=fixture("\xc8\x66\x55\xaa",25,250);
try {$decoder->decodeFrame(substr($good,0,-1)); ck(false,'truncate rejected');}catch(InvalidArgumentException $e){ck(true,'truncate rejected');}
try {$decoder->decodeFrame("\x00\x00"); ck(false,'short rejected');}catch(InvalidArgumentException $e){ck(true,'short rejected');}
try {new CgmGardenModeBPacket('foo',$headerTable);ck(false,'bad body table rejected');}catch(InvalidArgumentException $e){ck(true,'bad body table rejected');}
try {new CgmGardenModeBPacket($bodyTable,'foo');ck(false,'bad header table rejected');}catch(InvalidArgumentException $e){ck(true,'bad header table rejected');}
echo "CGM_PHASE33Y_PHP_CORRECTED_PASS assertions=$checks (SYNTHETIC ONLY)\n";
