<?php
declare(strict_types=1);
require __DIR__.'/_secure_packet.php';
$raw='TEST~MGANG123456789';
$expected=[
  0=>'352730301b25262f222a2257535157515351595b:abcdef466c5b97dae23e7d69c0bda84b833e5c057a77d20',
  1=>'352730301b25262f222a2257535157515351595b:abcdef2a05f5070dd50e972df51f8ac10f23c5b5bc11671',
  2=>'352730301b25262f222a2257535157515351595b:abcdefbda84b833e5c057a77d2466c5b97dae23e7d69c02',
];
$out=[];
foreach($expected as $sw=>$want){
  $got=cgm_encrypt_packet($raw,'abcdef',$sw);
  $out[]=['switch'=>$sw,'encrypt_ok'=>hash_equals($want,$got),'decrypt_ok'=>cgm_decrypt_packet($got)===$raw];
}
header('Content-Type: application/json');
echo json_encode(['ok'=>!in_array(false,array_merge(array_column($out,'encrypt_ok'),array_column($out,'decrypt_ok')),true),'tests'=>$out],JSON_UNESCAPED_SLASHES);
