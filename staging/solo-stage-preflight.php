<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit(1);
require '/var/www/html/_common.php';
require '/var/www/html/_solo_vip_pg.php';
$n=0;$pdo=null;
function check_stage(bool $ok): void {
    global $n;
    if (!$ok) throw new RuntimeException('assertion');
    ++$n;
}
try {
    if (getenv('CGM_SOLO_VIP_ENABLED')!=='1' || strlen(getenv('JPCG_ADMIN_KEY')?:'')<32)
        throw new RuntimeException('stage flag/key missing');
    $pdo=db();
    $pdo->beginTransaction();
    $s=new SoloPgStore($pdo);
    $now=time();$email='qa-'.bin2hex(random_bytes(16)).'@example.invalid';
    $c=solo_issue($s,7,$now);
    check_stage($c['email']===null);
    check_stage(strlen($c['code'])===14 && preg_match('/\AACGM[0-9]{10}\z/D',$c['code'])===1);
    check_stage($s->issue(['code_hash'=>$c['code_id'],'code_hint'=>'duplicate test','code_full'=>$c['code'],'email'=>null,'vip_days'=>365,'created_at'=>$now])===false);
    check_stage(solo_redeem($s,$email,'ACGM00000000',$now)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,$email,'ACGM2345678',$now)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,$email,'ACGM123456789',$now)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,$email,'ACGM12345678901',$now)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,$email,'ACGM123456789X',$now)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,$email,'MGANG123456789',$now)===['INVALID_CODE',0]);
    $find=static function() use ($s,$c): array {
        foreach ($s->recent() as $row) if ($row['code_id']===$c['code_id']) return $row;
        throw new RuntimeException('fixture not listed');
    };
    $unused=$find();
    check_stage($unused['email']===null && $unused['redeemed_at']===null && $unused['code_full']===$c['code'] && (int)$unused['vip_days']===7);
    check_stage($s->editEmail($c['code_id'],$email,'new-'.$email)===null);
    check_stage(solo_state($s->rows($email),$now)===['INACTIVE',0]);
    check_stage(solo_redeem($s,$email,'INVALID',$now)===['INVALID_CODE',0]);
    $expiry=$now+7*86400;
    check_stage(solo_redeem($s,$email,strtolower($c['code']),$now)===['ACTIVE',$expiry]);
    $bound=$find();
    check_stage($bound['email']===$email && $bound['code_full']===$c['code'] && (int)$bound['redeemed_at']===$now);
    check_stage(solo_redeem($s,'wrong-'.$email,$c['code'],$now+1)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,strtoupper($email),$c['code'],$now+1)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,$email,$c['code'],$now+1)===['ACTIVE',$expiry]);
    check_stage(solo_state($s->rows($email),$now+1)===['ACTIVE',$expiry]);
    check_stage(solo_state($s->rows($email),$expiry)===['EXPIRED',$expiry]);
    check_stage(solo_redeem($s,$email,$c['code'],$expiry)===['INVALID_CODE',0]);
    $newEmail='new-'.$email;
    check_stage($s->editEmail($c['code_id'],'wrong-'.$email,$newEmail)===null);
    $changed=$s->editEmail($c['code_id'],$email,$newEmail);
    check_stage($changed!==null && $changed['email']===$newEmail && $changed['redeemed_at']===$now && $changed['vip_expiry']===$expiry);
    $edited=$find();
    check_stage($edited['email']===$newEmail && $edited['code_full']===$c['code'] && (int)$edited['vip_days']===7 && (int)$edited['vip_expiry']===$expiry);
    check_stage($s->editEmail($c['code_id'],$email,'stale-'.$email)===null);
    check_stage(solo_state($s->rows($email),$now+2)===['INACTIVE',0]);
    check_stage(solo_state($s->rows($newEmail),$now+2)===['ACTIVE',$expiry]);
    check_stage(solo_redeem($s,$email,$c['code'],$now+2)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,$newEmail,$c['code'],$now+2)===['ACTIVE',$expiry]);
    check_stage($s->revoke($c['code_id'],$now+2)===true);
    check_stage($s->editEmail($c['code_id'],$newEmail,$email)===null);
    check_stage(solo_state($s->rows($newEmail),$now+2)===['REVOKED',0]);
    check_stage(solo_redeem($s,$newEmail,$c['code'],$now+3)===['INVALID_CODE',0]);
    $other=solo_issue($s,15,$now);
    check_stage($s->revoke($other['code_id'],$now)===true);
    check_stage(solo_redeem($s,$email,$other['code'],$now+1)===['INVALID_CODE',0]);
    // Preserve already-issued pre-assigned legacy Solo rows.
    $legacyCode='MGANG-SOLO-'.strtoupper(bin2hex(random_bytes(16)));
    $legacyHash=hash('sha256',$legacyCode);
    $s->issue(['code_hash'=>$legacyHash,'code_hint'=>'legacy test','email'=>$email,'vip_days'=>1,'created_at'=>$now]);
    check_stage(solo_redeem($s,'wrong-'.$email,$legacyCode,$now)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,$email,$legacyCode,$now)===['ACTIVE',$now+86400]);
    // Old unassigned full codes remain valid after the format migration.
    $legacyFree='MGANG-SOLO-'.strtoupper(bin2hex(random_bytes(16)));
    $legacyFreeHash=hash('sha256',$legacyFree);
    $legacyEmail='legacy-'.$email;
    check_stage($s->issue(['code_hash'=>$legacyFreeHash,'code_hint'=>'legacy unassigned','code_full'=>$legacyFree,'email'=>null,'vip_days'=>2,'created_at'=>$now])===true);
    check_stage(solo_redeem($s,$legacyEmail,$legacyFree,$now)===['ACTIVE',$now+2*86400]);
    check_stage(solo_redeem($s,$email,$legacyFree,$now)===['INVALID_CODE',0]);
    // Leading zero is retained as text. Previously generated 12-character codes stay valid.
    $zeroCode='ACGM0';
    for ($i=0;$i<9;++$i) $zeroCode.=(string)random_int(0,9);
    $zeroHash=hash('sha256',$zeroCode);$zeroEmail='zero-'.$email;
    check_stage($s->issue(['code_hash'=>$zeroHash,'code_hint'=>'leading zero QA','code_full'=>$zeroCode,'email'=>null,'vip_days'=>3,'created_at'=>$now])===true);
    check_stage(solo_redeem($s,$zeroEmail,$zeroCode,$now)===['ACTIVE',$now+3*86400]);
    $alphaCode='ACGMX';$alphabet='23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    for ($i=0;$i<7;++$i) $alphaCode.=$alphabet[random_int(0,strlen($alphabet)-1)];
    $alphaHash=hash('sha256',$alphaCode);$alphaEmail='alpha-'.$email;
    check_stage($s->issue(['code_hash'=>$alphaHash,'code_hint'=>'old alpha QA','code_full'=>$alphaCode,'email'=>null,'vip_days'=>4,'created_at'=>$now])===true);
    check_stage(solo_redeem($s,$alphaEmail,strtolower($alphaCode),$now)===['ACTIVE',$now+4*86400]);
    check_stage(solo_redeem($s,$email,$alphaCode,$now)===['INVALID_CODE',0]);
    $key=hash('sha256',random_bytes(32));$bucket=intdiv($now,60);
    check_stage($s->allow($key,$bucket,2)===true);
    check_stage($s->allow($key,$bucket,2)===true);
    check_stage($s->allow($key,$bucket,2)===false);
    $pdo->rollBack();
    if ($s->rows($email)!==[] || $s->rows($newEmail)!==[] || $s->rows($legacyEmail)!==[] || $s->rows($zeroEmail)!==[] || $s->rows($alphaEmail)!==[]) throw new RuntimeException('rollback verification failed');
    echo "SOLO_STAGE_PDO_ACCEPTANCE_PASS {$n}/48; synthetic transaction rolled back; TLS verified\n";
} catch(Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    // Never log database credentials, DSNs or SQL error text.
    $state=$e instanceof PDOException ? (string)$e->getCode() : get_class($e);
    // Only fixed diagnostic labels are emitted. No environment values or exception text.
    $reason='unclassified';
    $message=$e->getMessage();
    if ($message==='stage flag/key missing') {
        $reason=getenv('CGM_SOLO_VIP_ENABLED')!=='1'?'feature_flag_not_1':'admin_key_missing_or_short';
    } elseif ($message==='staging database configuration rejected') {
        $url=getenv('DATABASE_URL')?:'';
        $host=getenv('CGM_SOLO_STAGE_DB_HOST')?:'';
        $p=parse_url($url);
        if ($url==='') $reason='database_url_missing';
        elseif (!$p || ($p['scheme']??'')!=='postgresql') $reason='database_url_format_invalid';
        elseif ($host==='') $reason='database_host_setting_missing';
        elseif (($p['host']??'')!==$host) $reason='database_url_host_mismatch';
        elseif (!preg_match('/\A[a-z0-9.-]+\.neon\.tech\z/D',$host)) $reason='database_host_not_neon';
        elseif (($p['path']??'')!=='/cgm_solo_stage_r43159') $reason='wrong_database_name';
        elseif ((int)($p['port']??5432)!==5432) $reason='wrong_database_port';
        elseif (rawurldecode($p['user']??'')!=='solo_stage_r43159_app') $reason='wrong_database_role';
        elseif (empty($p['pass'])) $reason='database_url_password_missing';
        else $reason='database_configuration_rejected';
    } elseif ($message==='staging database identity mismatch') {
        $reason='database_identity_mismatch';
    } elseif ($message==='assertion') {
        $reason='acceptance_assertion_failed';
    } elseif ($message==='rollback verification failed') {
        $reason='rollback_verification_failed';
    } elseif ($e instanceof PDOException) {
        $reason='pdo_connection_or_query_failed';
    }
    fwrite(STDERR,"SOLO_STAGE_PDO_ACCEPTANCE_FAIL check={$n} category={$state} reason={$reason}\n");
    exit(1);
}
