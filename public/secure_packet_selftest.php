<?php
declare(strict_types=1);

require __DIR__.'/_secure_packet.php';

$raw='TEST~MGANG123456789';

$expected=[
  0=>'352730301b2b26232d23545452565652525e58:abcdefec84a32772280c28809bbda84b833e5c057a77d20',
  1=>'352730301b2b26232d23545452565652525e58:abcdef5141a0d34e3e0cf954cd1f8ac10f23c5b5bc11671',
  2=>'352730301b2b26232d23545452565652525e58:abcdefbda84b833e5c057a77d2ec84a32772280c28809b2',
];

$out=[];

foreach($expected as $sw=>$want){
    $got=cgm_encrypt_packet($raw,'abcdef',$sw);

    $out[]=[
        'switch'=>$sw,
        'encrypt_ok'=>hash_equals($want,$got),
        'decrypt_ok'=>cgm_decrypt_packet($got)===$raw
    ];
}

header('Content-Type: application/json');

echo json_encode([
    'ok'=>!in_array(
        false,
        array_merge(
            array_column($out,'encrypt_ok'),
            array_column($out,'decrypt_ok')
        ),
        true
    ),
    'tests'=>$out
],JSON_UNESCAPED_SLASHES);