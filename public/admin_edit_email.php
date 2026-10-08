<?php
declare(strict_types=1);
require __DIR__.'/_common.php';

if (!admin_ok()) respond(['ok'=>false,'error'=>'unauthorized'],401);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(['ok'=>false,'error'=>'Use POST to edit an email'],405);
}

$input=body();
foreach (['code_id','expected_login_id','email'] as $field) {
    if (!isset($input[$field]) || !is_string($input[$field])) {
        respond(['ok'=>false,'error'=>'Missing or invalid '.$field],400);
    }
}
$codeId=$input['code_id'];
$oldLogin=$input['expected_login_id'];
$email=trim($input['email']);
if (!preg_match('/^[a-f0-9]{64}$/D',$codeId)) respond(['ok'=>false,'error'=>'Invalid record ID'],400);
if (strlen($oldLogin)>1024 || strlen($email)>254 || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
    respond(['ok'=>false,'error'=>'Enter a valid login email without an account prefix'],400);
}

// Preserve the APK account namespace exactly, e.g. INPP:email@example.com.
$separator=strrpos($oldLogin,':');
$prefix=$separator===false?'':substr($oldLogin,0,$separator+1);
$oldEmail=$separator===false?$oldLogin:substr($oldLogin,$separator+1);
if (!filter_var($oldEmail,FILTER_VALIDATE_EMAIL)) {
    respond(['ok'=>false,'error'=>'This login ID is not an editable email'],400);
}
$newLogin=$prefix.$email;

try {
    $db=db();
    // A single conditional UPDATE also prevents stale browser edits from
    // overwriting a membership reassigned by another admin. No expiry update.
    $q=$db->prepare('UPDATE codes SET login_id=? WHERE code_hash=? AND login_id=? AND redeemed_at IS NOT NULL RETURNING login_id,vip_expiry');
    $q->execute([$newLogin,$codeId,$oldLogin]);
    $row=$q->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(['ok'=>false,'error'=>'Record is unused, missing, or changed. Refresh and try again.'],409);
    error_log('[CGM_ADMIN_EMAIL_EDIT] code_hash='.$codeId.' old_login_hash='.hash('sha256',$oldLogin).' new_login_hash='.hash('sha256',$newLogin));
    respond(['ok'=>true,'login_id'=>$row['login_id'],'vip_expiry'=>(int)$row['vip_expiry']]);
} catch (Throwable $e) {
    error_log('[CGM_ADMIN_EMAIL_EDIT_ERROR] database operation failed');
    respond(['ok'=>false,'error'=>'Could not update membership email. Try again later.'],500);
}
