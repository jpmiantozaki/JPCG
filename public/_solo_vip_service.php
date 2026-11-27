<?php
declare(strict_types=1);
// Independent Solo license service. No game password, session or game protocol.
interface SoloStore {
    public function issue(array $row): void;
    public function rows(string $email): array;
    public function redeem(string $hash, string $email, string $code, int $now): ?int;
    public function revoke(string $hash, int $now): bool;
    public function recent(): array;
    public function allow(string $key, int $bucket, int $limit): bool;
}
final class SoloInput extends RuntimeException {}
function solo_email(mixed $v): string {
    if (!is_string($v) || strlen($v)>254 || $v!==trim($v) ||
        preg_match('/[\x00-\x20\x7f~]/', $v) || filter_var($v,FILTER_VALIDATE_EMAIL)===false ||
        substr_count($v,'@')!==1 || !str_contains(substr($v,strrpos($v,'@')+1),'.')) {
        throw new SoloInput('invalid_email');
    }
    return $v; // exact case and identity, no alias/prefix conversion
}
function solo_state(array $rows, int $now): array {
    $expiry=0; $revoked=false;
    foreach ($rows as $r) {
        if ($r['revoked_at']!==null) { $revoked=true; continue; }
        if ($r['redeemed_at']!==null) $expiry=max($expiry,(int)$r['vip_expiry']);
    }
    if ($expiry>$now) return ['ACTIVE',$expiry];
    if ($expiry>0) return ['EXPIRED',$expiry];
    return [$revoked?'REVOKED':'INACTIVE',0];
}
function solo_issue(SoloStore $store, int $days, int $now): array {
    if ($days<1 || $days>3650) throw new SoloInput('invalid_days');
    // Distinct namespace; 128 random bits, not the 9-digit legacy mobile code.
    $code='MGANG-SOLO-'.strtoupper(bin2hex(random_bytes(16)));
    $row=['code_hash'=>hash('sha256',$code),'code_hint'=>substr($code,0,15).'...'.substr($code,-4),
        'email'=>null,'vip_days'=>$days,'created_at'=>$now,'redeemed_at'=>null,'vip_expiry'=>null,'revoked_at'=>null];
    $store->issue($row);
    return ['code'=>$code,'code_id'=>$row['code_hash'],'email'=>null,'days'=>$days];
}
function solo_redeem(SoloStore $store,string $email,mixed $code,int $now): array {
    if (!is_string($code) || strlen($code)>80) return ['INVALID_CODE',0];
    $code=strtoupper(trim($code));
    if (!preg_match('/\AMGANG-SOLO-[A-F0-9]{32}\z/D',$code)) return ['INVALID_CODE',0];
    $expiry=$store->redeem(hash('sha256',$code),$email,$code,$now);
    return $expiry!==null && $expiry>$now?['ACTIVE',$expiry]:['INVALID_CODE',0];
}
