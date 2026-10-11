<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_cgm_membership.php';
require_once __DIR__ . '/_authfirst_resolve.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function af_reply(string $state, int $expiry, int $code, ?string $requestId): never {
    $now = time();
    $eligible = $state === 'ACTIVE' && $expiry > $now;
    if ($state === 'ACTIVE' && !$eligible) $state = 'EXPIRED';
    if ($requestId !== null) {
        // Strict W client format. Only four statuses; expired is an INACTIVE decision.
        $status = match ($state) { 'ACTIVE' => 'ACTIVE', 'EXPIRED', 'INACTIVE' => 'INACTIVE', default => 'ERROR' };
        $ttl = $status === 'ACTIVE' ? min(5000, max(0, ($expiry - $now) * 1000 - 1000)) : 0;
        if ($status === 'ACTIVE' && $ttl === 0) $status = 'ERROR';
        echo json_encode(['schema'=>'cgm-email-membership-v1','request_id'=>$requestId,'status'=>$status,
            'server_time'=>$now,'membership_expires_at'=>max(0, $expiry),'ttl_ms'=>$ttl], JSON_INVALID_UTF8_SUBSTITUTE);
    } else {
        // Preserve previously deployed plain-email contract exactly.
        http_response_code($code);
        echo json_encode(['scope'=>'CGM_MEMBERSHIP_ONLY','state'=>$state,'eligible_to_attempt'=>$eligible,
            'game_authorized'=>false,'vip_expiry'=>max(0,$expiry),'server_time'=>$now,
            'message'=>match($state){'ACTIVE'=>'CGM membership active.','EXPIRED'=>'CGM membership expired.',
                'INACTIVE'=>'CGM membership inactive or not found.',default=>'Membership lookup unavailable.'}],
            JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); af_reply('ERROR',0,405,null); }
$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '',2)[0]));
if ($contentType !== 'application/json') af_reply('ERROR',0,415,null);
$raw = file_get_contents('php://input', false, null, 0, 1025);
if ($raw === false || strlen($raw)>1024) af_reply('ERROR',0,400,null);
try { $request = json_decode($raw,true,4,JSON_THROW_ON_ERROR); } catch (JsonException $e) { af_reply('ERROR',0,400,null); }
if (!is_array($request)) af_reply('ERROR',0,400,null);
$keys = array_keys($request); sort($keys);
$isLegacy = $keys === ['login_id'] && is_string($request['login_id']);
$isW = $keys === ['email','request_id','schema'] && ($request['schema'] ?? null) === 'cgm-email-membership-v1'
    && is_string($request['email'] ?? null) && is_string($request['request_id'] ?? null)
    && (bool)preg_match('/\A[A-Za-z0-9-]{1,64}\z/D', $request['request_id']);
if (!$isLegacy && !$isW) af_reply('ERROR',0,400,null);
$login = $isW ? $request['email'] : $request['login_id'];
$rid = $isW ? $request['request_id'] : null;
if ($login !== trim($login) || strlen($login)<1 || strlen($login)>320 || preg_match('/[\x00-\x1F\x7F~]/', $login)) af_reply('ERROR',0,400,$rid);
if ($isW && (strlen($login)>254 || filter_var($login,FILTER_VALIDATE_EMAIL)===false)) af_reply('ERROR',0,400,$rid);
try {
    $pdo=db(); $now=time();
    $result=authfirst_resolve($login,static fn(string $candidate):array=>cgm_membership_lookup($pdo,$candidate,$now));
    $state=(string)($result['state']??'ERROR');$expiry=(int)($result['vip_expiry']??0);
    if (!in_array($state,['ACTIVE','INACTIVE','EXPIRED'],true)) af_reply('ERROR',0,503,$rid);
    af_reply($state,$expiry,200,$rid);
} catch(Throwable $e) { error_log('[CGM_AUTHFIRST] lookup_failure');af_reply('ERROR',0,503,$rid); }
