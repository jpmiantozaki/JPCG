<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/CgmPcPacketCodec.php';
use CGM\Phase33W\CgmPcPacketCodec as Codec;

$passed=0;
function check(bool $ok,string $label):void{
    global $passed;
    if(!$ok) throw new RuntimeException('FAIL '.$label);
    $passed++;
}
$frame=hex2bin('0800010000000101');
check(bin2hex(Codec::wrapInnerFrame($frame,'123'))==='0e007e3132333135343436303232',
    '3-byte fixture matches independently derived Python vector');
check(bin2hex(Codec::wrapInnerFrame($frame,'1234567'))==='12007c313233343536373135343131353431',
    '7-byte fixture matches independently derived Python vector');

foreach(range(2,9) as $klen) {
    $key=substr('ABCDEFGHI',$klen*-1);
    foreach([6,7,8,9,57,161,255,511,1000] as $dataSize) {
        $payload='';
        for($i=0;$i<$dataSize;$i++) $payload.=chr(($i*13+7)&255);
        $inner=pack('v',$dataSize+2).$payload;
        $encoded=Codec::wrapInnerFrame($inner,$key);
        check(strlen($encoded)===strlen($inner)+$klen+3,'size relation');
        check(Codec::unwrapOuterFrame($encoded)===$inner,'roundtrip');
    }
}
$bad=substr(Codec::wrapInnerFrame($frame,'123'),0,-1);
try { Codec::unwrapOuterFrame($bad); check(false,'truncated'); }
catch(InvalidArgumentException $e) { check(true,'truncated rejected'); }
try { Codec::wrapInnerFrame(hex2bin('0000').'abcdef','123'); check(false,'invalid length'); }
catch(InvalidArgumentException $e) { check(true,'invalid length rejected'); }
try { Codec::scramble('payload','X'); check(false,'invalid key'); }
catch(InvalidArgumentException $e) { check(true,'invalid key rejected'); }
for ($i=0;$i<30;$i++) {
    $encoded=Codec::wrapInnerFrame($frame);
    check(Codec::unwrapOuterFrame($encoded)===$frame,'CSPRNG fixture roundtrip');
}
echo "CGM_PHASE33W_PHP_CODEC_PASS assertions=$passed (SYNTHETIC ONLY)\n";
