<?php
declare(strict_types=1);
require_once __DIR__.'/_solo_vip_service.php';
function solo_reply(array $payload,int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
    exit;
}
function solo_request(): array {
    if (($_SERVER['REQUEST_METHOD']??'')!=='POST') { header('Allow: POST');solo_reply(['error'=>'method_not_allowed'],405); }
    if (($_SERVER['QUERY_STRING']??'')!=='') solo_reply(['error'=>'query_not_allowed'],400);
    if (strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'',2)[0]))!=='application/json') solo_reply(['error'=>'content_type'],415);
    $raw=file_get_contents('php://input',false,null,0,4097);
    if ($raw===false || strlen($raw)>4096) solo_reply(['error'=>'request_too_large'],413);
    try { $b=json_decode($raw,true,8,JSON_THROW_ON_ERROR); }
    catch (JsonException) { solo_reply(['error'=>'invalid_json'],400); }
    if (!is_array($b) || array_is_list($b)) solo_reply(['error'=>'invalid_request'],400);
    return $b;
}
function solo_keys(array $b,array $expected): void {
    $keys=array_keys($b);sort($keys);sort($expected);
    if ($keys!==$expected) throw new SoloInput('invalid_fields');
}
function solo_dispatch(callable $factory,string $adminKey,bool $admin=false): never {
    $b=solo_request();
    try {
        $action=$b['action']??null;
        if ($admin) {
            $provided=$_SERVER['HTTP_X_ADMIN_KEY']??'';
            if ($adminKey==='' || !is_string($provided) || !hash_equals($adminKey,$provided)) solo_reply(['error'=>'unauthorized'],401);
            if ($action==='generate') {
                solo_keys($b,['action','email','days']); $email=solo_email($b['email']);
                if (!is_int($b['days']) || $b['days']<1 || $b['days']>3650) throw new SoloInput('invalid_days');
                $store=$factory(); solo_reply(['ok'=>true]+solo_issue($store,$email,$b['days'],time()));
            }
            if ($action==='list') {
                solo_keys($b,['action']); $store=$factory(); solo_reply(['ok'=>true,'codes'=>$store->recent(),'server_time'=>time()]);
            }
            if ($action==='revoke') {
                solo_keys($b,['action','code_id']);
                if (!is_string($b['code_id']) || !preg_match('/\A[a-f0-9]{64}\z/D',$b['code_id'])) throw new SoloInput('invalid_code_id');
                $store=$factory();solo_reply(['ok'=>true,'changed'=>$store->revoke($b['code_id'],time())]);
            }
            throw new SoloInput('invalid_action');
        }
        if ($action==='lookup') solo_keys($b,['action','schema','request_id','email']);
        elseif ($action==='redeem') solo_keys($b,['action','schema','request_id','email','code']);
        else throw new SoloInput('invalid_action');
        if (($b['schema']??null)!=='cgm-solo-vip-v1' || !is_string($b['request_id']) || !preg_match('/\A[a-f0-9]{32}\z/D',$b['request_id'])) throw new SoloInput('invalid_schema');
        $email=solo_email($b['email']);
        if ($adminKey==='') throw new RuntimeException('missing configuration');
        $store=$factory();$now=time();
        // Use the actual remote address, never a caller-supplied forwarding header.
        // Confirm proxy behavior during staging: shared proxy addresses may share a limit.
        $rateKey=hash_hmac('sha256',$action.'|'.($_SERVER['REMOTE_ADDR']??'unknown'),$adminKey);
        if (!$store->allow($rateKey,intdiv($now,60),$action==='redeem'?10:60)) solo_reply(['error'=>'rate_limited'],429);
        [$state,$expiry]=$action==='lookup'?solo_state($store->rows($email),$now):solo_redeem($store,$email,$b['code'],$now);
        solo_reply(['schema'=>'cgm-solo-vip-v1','scope'=>'SOLO_MEMBERSHIP_ONLY','request_id'=>$b['request_id'],'email'=>$email,
            'state'=>$state,'expiry'=>$expiry,'server_time'=>$now,'game_authorized'=>false]);
    } catch(SoloInput $e) { solo_reply(['error'=>$e->getMessage()],400); }
    catch(Throwable $e) { error_log('[CGM_SOLO_VIP] request failed'); solo_reply(['error'=>'service_unavailable'],503); }
}
