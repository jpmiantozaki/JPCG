<?php
declare(strict_types=1);
require __DIR__.'/_common.php';

$login=value('login');
if($login==='') respond(['ok'=>false,'state'=>'missing_login'],400);

try {
    $db=db();

    // Existing JPCG schema stores the bound login identifier in codes.login_id.
    // Return only entitlement state and expiry; never expose codes or sessions.
    $q=$db->prepare(
        'SELECT vip_expiry
           FROM codes
          WHERE login_id=?
            AND redeemed_at IS NOT NULL
          ORDER BY vip_expiry DESC
          LIMIT 1'
    );
    $q->execute([$login]);
    $row=$q->fetch(PDO::FETCH_ASSOC);

    if(!$row || $row['vip_expiry']===null){
        respond(['ok'=>true,'state'=>'not_activated','vip_expiry'=>null]);
    }

    $expiry=(int)$row['vip_expiry'];
    respond([
        'ok'=>true,
        'state'=>$expiry>time()?'active':'expired',
        'vip_expiry'=>$expiry
    ]);
} catch(Throwable $e){
    error_log('[JPCG_ACCOUNT_STATUS_ERROR] '.$e->getMessage());
    respond(['ok'=>false,'state'=>'server_error'],500);
}
