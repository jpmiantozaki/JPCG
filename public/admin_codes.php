<?php
require __DIR__.'/_common.php';
if(!admin_ok()) respond(['ok'=>false,'error'=>'unauthorized'],401);
$db=db();
$rows=$db->query('SELECT code_hint,code_full,vip_days,created_at,redeemed_at,login_id,vip_expiry FROM codes ORDER BY created_at DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as &$r){
    if($r['login_id']!==null) $r['login_id_hash']=hash('sha256',(string)$r['login_id']);
    // The authenticated admin may view the original login ID.
    $r['code_available']=$r['code_full']!==null;
    $r['state']=$r['redeemed_at']===null?'unused':(((int)$r['vip_expiry']>time())?'active':'expired');
}
respond(['ok'=>true,'codes'=>$rows]);
