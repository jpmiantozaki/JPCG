<?php
require __DIR__.'/_common.php';
if (!admin_ok()) respond(['ok'=>false,'error'=>'unauthorized'],401);

$count=max(1,min(100,(int)(value('count','1'))));
$days=max(1,min(3650,(int)(value('days',(string)DEFAULT_VIP_DAYS))));
$db=db();
$out=[];

for($i=0;$i<$count;$i++){
    do {
        $code='CGMANG'.str_pad((string)random_int(0,999999999),9,'0',STR_PAD_LEFT);
        $hash=code_hash($code);
        $q=$db->prepare('SELECT 1 FROM codes WHERE code_hash=?');
        $q->execute([$hash]);
    } while($q->fetchColumn());

    $q=$db->prepare('INSERT INTO codes(code_hash,code_hint,vip_days,created_at) VALUES(?,?,?,?)');
    $q->execute([$hash,substr($code,0,8).'****', $days,time()]);
    $out[]=$code;
}
error_log('[JPCG_ADMIN_GENERATE] count='.$count.' days='.$days);
respond(['ok'=>true,'days'=>$days,'codes'=>$out]);
