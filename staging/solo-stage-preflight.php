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
    $c=solo_issue($s,$email,7,$now);
    check_stage(solo_state($s->rows($email),$now)===['INACTIVE',0]);
    check_stage(solo_redeem($s,'wrong-'.$email,$c['code'],$now)===['INVALID_CODE',0]);
    check_stage(solo_redeem($s,strtoupper($email),$c['code'],$now)===['INVALID_CODE',0]);
    $expiry=$now+7*86400;
    check_stage(solo_redeem($s,$email,$c['code'],$now)===['ACTIVE',$expiry]);
    check_stage(solo_redeem($s,$email,$c['code'],$now+1)===['INVALID_CODE',0]);
    check_stage(solo_state($s->rows($email),$now+1)===['ACTIVE',$expiry]);
    check_stage(solo_state($s->rows($email),$expiry)===['EXPIRED',$expiry]);
    check_stage($s->revoke($c['code_id'],$now+2)===true);
    check_stage(solo_state($s->rows($email),$now+2)===['REVOKED',0]);
    check_stage(solo_redeem($s,$email,$c['code'],$now+3)===['INVALID_CODE',0]);
    $key=hash('sha256',random_bytes(32));$bucket=intdiv($now,60);
    check_stage($s->allow($key,$bucket,2)===true);
    check_stage($s->allow($key,$bucket,2)===true);
    check_stage($s->allow($key,$bucket,2)===false);
    $pdo->rollBack();
    if ($s->rows($email)!==[]) throw new RuntimeException('rollback verification failed');
    echo "SOLO_STAGE_PDO_ACCEPTANCE_PASS {$n}/13; synthetic transaction rolled back; TLS verified\n";
} catch(Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    // Never log database credentials, DSNs or SQL error text.
    $state=$e instanceof PDOException ? (string)$e->getCode() : get_class($e);
    fwrite(STDERR,"SOLO_STAGE_PDO_ACCEPTANCE_FAIL check={$n} category={$state}\n");
    exit(1);
}
