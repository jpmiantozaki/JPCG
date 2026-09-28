<?php
require __DIR__.'/_common.php';
$session=value('session');
$db=db(); clean_sessions($db);
$q=$db->prepare('SELECT login_id,stage,vip_expiry,session_expiry FROM sessions WHERE session=?');
$q->execute([$session]);
$s=$q->fetch(PDO::FETCH_ASSOC);
if(!$s) respond(['ok'=>false,'state'=>'missing_or_expired'],404);
respond(['ok'=>true,'stage'=>(int)$s['stage'],'servertype'=>(int)$s['stage'],
         'vip_expiry'=>(int)$s['vip_expiry'],'session_expiry'=>(int)$s['session_expiry']]);
