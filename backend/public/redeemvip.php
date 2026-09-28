<?php
require __DIR__.'/_common.php';
$login=value('login'); $code=strtoupper(value('code'));
if($login==='') respond(relay(true,'Missing login identifier','',0,'',0),400);
if(!preg_match('/^JPANL[0-9]{9}$/',$code)) respond(relay(true,'Invalid JPANL code','',0,'',0),400);
$db=db(); clean_sessions($db); $db->beginTransaction();
try {
 $q=$db->prepare('SELECT * FROM codes WHERE code_hash=? FOR UPDATE'); $q->execute([code_hash($code)]);
 $row=$q->fetch(PDO::FETCH_ASSOC);
 if(!$row){$db->rollBack(); respond(relay(true,'Invalid JPANL code','',0,'',0),404);}
 if($row['redeemed_at']!==null){
   if((string)$row['login_id']!==$login){$db->rollBack(); respond(relay(true,'JPANL code already redeemed','',0,'',0),409);}
   $vip=(int)$row['vip_expiry'];
 } else {
   $vip=time()+((int)$row['vip_days']*86400);
   $u=$db->prepare('UPDATE codes SET redeemed_at=?,login_id=?,vip_expiry=? WHERE code_hash=? AND redeemed_at IS NULL');
   $u->execute([time(),$login,$vip,code_hash($code)]);
   if($u->rowCount()!==1){$db->rollBack(); respond(relay(true,'Redemption conflict','',0,'',0),409);}
 }
 if($vip<=time()){$db->rollBack(); respond(relay(true,'VIP expired','',0,'',0),403);}
 $session=token(); $data=token(18);
 $s=$db->prepare('INSERT INTO sessions(session,login_id,relay_data,stage,vip_expiry,session_expiry,created_at) VALUES(?,?,?,?,?,?,?)');
 $s->execute([$session,$login,$data,0,$vip,time()+SESSION_TTL,time()]);
 $db->commit();
 error_log('[JPCG_REDEEM] login_hash='.hash('sha256',$login).' session='.substr($session,0,8).'... returning='.($row['redeemed_at']!==null?'yes':'no'));
 respond(relay(false,'',$data,0,$session,$vip));
} catch(Throwable $e){
 if($db->inTransaction()) $db->rollBack();
 error_log('[JPCG_REDEEM_ERROR] '.$e->getMessage());
 respond(relay(true,'Server error','',0,'',0),500);
}
