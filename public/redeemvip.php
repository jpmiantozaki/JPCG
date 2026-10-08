<?php
declare(strict_types=1);
require __DIR__.'/_common.php';
require __DIR__.'/_secure_packet.php';

// IMPORTANT: the unchanged APK only decrypts/parses the body when the HTTP
// response itself is successful. Therefore membership outcomes are returned
// as protected MGRelayResponse payloads with HTTP 200; `error` carries the
// logical success/failure state expected by the client.
function redeem_reply(bool $error, string $message, int $expiry=0): never {
    cgm_secure_respond(relay($error,$message,'',0,'',$expiry),200);
}

try {
    $protected=value('data');
    if($protected==='') redeem_reply(true,'Missing protected redemption data');

    $raw=cgm_decrypt_packet($protected);
    $parts=explode('~',$raw,2);
    $login=trim((string)($parts[0]??''));
    $code=strtoupper(trim((string)($parts[1]??'')));

    if($login==='') redeem_reply(true,'Missing login identifier');
    if(!preg_match('/^MGANG[0-9]{9}$/',$code)) redeem_reply(true,'Invalid membership code');

    $db=db();
    clean_sessions($db);
    $db->beginTransaction();

    $q=$db->prepare('SELECT * FROM codes WHERE code_hash=? FOR UPDATE');
    $q->execute([code_hash($code)]);
    $row=$q->fetch(PDO::FETCH_ASSOC);
    if(!$row){
        $db->rollBack();
        redeem_reply(true,'Invalid membership code');
    }

    $returning=$row['redeemed_at']!==null;
    if($returning){
        if((string)$row['login_id']!==$login){
            $db->rollBack();
            redeem_reply(true,'Membership code already redeemed');
        }
        $vip=(int)$row['vip_expiry'];
    } else {
        $vip=time()+((int)$row['vip_days']*86400);
        $u=$db->prepare('UPDATE codes SET redeemed_at=?,login_id=?,vip_expiry=?,code_full=? WHERE code_hash=? AND redeemed_at IS NULL');
        $u->execute([time(),$login,$vip,$code,code_hash($code)]);
        if($u->rowCount()!==1){
            $db->rollBack();
            redeem_reply(true,'Redemption conflict');
        }
    }

    if($vip<=time()){
        $db->rollBack();
        redeem_reply(true,'VIP Membership has expired.');
    }

    // Backfill a legacy full code only after a successful redemption by
    // its bound account; do not change its original expiry.
    if($returning && $row['code_full']===null){
        $u=$db->prepare('UPDATE codes SET code_full=? WHERE code_hash=? AND code_full IS NULL');
        $u->execute([$code,code_hash($code)]);
    }

    $db->commit();
    $days=max(1,(int)$row['vip_days']);
    $message=$returning
        ? 'VIP Membership is already active for this account.'
        : "You've successfully activated {$days} Days VIP Membership.";

    error_log('[CGM_REDEEM] login_hash='.hash('sha256',$login).' vip_expiry='.$vip.' returning='.($returning?'1':'0'));
    redeem_reply(false,$message,$vip);
} catch(Throwable $e) {
    if(isset($db) && $db instanceof PDO && $db->inTransaction()) $db->rollBack();
    error_log('[CGM_REDEEM_ERROR] '.$e->getMessage());
    redeem_reply(true,'VIP Redemption Error. Please try again later.');
}
