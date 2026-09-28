<?php
require __DIR__.'/_common.php';
$session=value('session');
$data=value('data');
$db=db();
clean_sessions($db);

$q=$db->prepare('SELECT * FROM sessions WHERE session=?');
$q->execute([$session]);
$s=$q->fetch(PDO::FETCH_ASSOC);
if(!$s) respond(relay(true,'Invalid or expired session','',0,'',0),401);
if(!hash_equals((string)$s['relay_data'],$data)) respond(relay(true,'Relay data mismatch','',0,'',0),401);
if((int)$s['vip_expiry']<=time()) respond(relay(true,'VIP expired','',0,'',0),403);

$newData=token(18);
$u=$db->prepare('UPDATE sessions SET relay_data=?,stage=1,session_expiry=? WHERE session=?');
$u->execute([$newData,time()+SESSION_TTL,$session]);
error_log('[JPCG_VERIFY] session='.substr($session,0,8).'... stage=game');
respond(relay(false,'',$newData,1,$session,(int)$s['vip_expiry']));
