<?php
declare(strict_types=1);
require_once __DIR__.'/_solo_vip_service.php';
// Uses existing JPCG db() connection (DATABASE_URL), with no new credentials.
// The existing helper may perform its original idempotent legacy schema checks.
final class SoloPgStore implements SoloStore {
    public function __construct(private PDO $pdo) {}
    public function issue(array $r): bool {
        $s=$this->pdo->prepare('INSERT INTO public.cgm_solo_vip_codes(code_hash,code_hint,code_full,email,vip_days,created_at) VALUES (?,?,?,?,?,?) ON CONFLICT (code_hash) DO NOTHING RETURNING code_hash');
        $s->execute([$r['code_hash'],$r['code_hint'],$r['code_full']??null,$r['email'],$r['vip_days'],$r['created_at']]);
        return $s->fetchColumn()!==false;
    }
    public function rows(string $email): array {
        $s=$this->pdo->prepare('SELECT redeemed_at,vip_expiry,revoked_at FROM public.cgm_solo_vip_codes WHERE email=?');
        $s->execute([$email]); return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    public function redeem(string $hash,string $email,string $code,int $now): ?int {
        // A single conditional write is atomic under concurrent redemption and revocation.
        // The first successful claimant binds the code. Legacy pre-assigned rows stay restricted.
        // New codes are retained for authenticated admins; old hash-only rows can be filled on redemption.
        $s=$this->pdo->prepare('UPDATE public.cgm_solo_vip_codes SET email=?,code_full=?,redeemed_at=CAST(? AS bigint),vip_expiry=CAST(? AS bigint)+CAST(vip_days AS bigint)*86400 WHERE code_hash=? AND (email IS NULL OR email=?) AND redeemed_at IS NULL AND revoked_at IS NULL RETURNING vip_expiry');
        $s->execute([$email,$code,$now,$now,$hash,$email]);
        $expiry=$s->fetchColumn();
        if ($expiry!==false) return (int)$expiry;
        // Idempotent replay by the same account while active. Never restart the duration.
        $s=$this->pdo->prepare('SELECT vip_expiry FROM public.cgm_solo_vip_codes WHERE code_hash=? AND email=? AND redeemed_at IS NOT NULL AND revoked_at IS NULL AND vip_expiry>CAST(? AS bigint)');
        $s->execute([$hash,$email,$now]);
        $expiry=$s->fetchColumn(); return $expiry===false?null:(int)$expiry;
    }
    public function editEmail(string $hash,string $expectedEmail,string $newEmail): ?array {
        // Compare-and-set ownership. Never alter redemption, expiration, duration or code.
        $s=$this->pdo->prepare('UPDATE public.cgm_solo_vip_codes SET email=? WHERE code_hash=? AND email=? AND redeemed_at IS NOT NULL AND revoked_at IS NULL RETURNING email,redeemed_at,vip_expiry');
        $s->execute([$newEmail,$hash,$expectedEmail]);
        $row=$s->fetch(PDO::FETCH_ASSOC);
        return $row===false?null:['email'=>$row['email'],'redeemed_at'=>(int)$row['redeemed_at'],'vip_expiry'=>(int)$row['vip_expiry']];
    }
    public function revoke(string $hash,int $now): bool {
        $s=$this->pdo->prepare('UPDATE public.cgm_solo_vip_codes SET revoked_at=CAST(? AS bigint) WHERE code_hash=? AND revoked_at IS NULL');
        $s->execute([$now,$hash]); return $s->rowCount()===1;
    }
    public function recent(): array {
        return $this->pdo->query('SELECT code_hash AS code_id,code_hint,code_full,email,vip_days,created_at,redeemed_at,vip_expiry,revoked_at FROM public.cgm_solo_vip_codes ORDER BY created_at DESC,code_hash DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
    }
    public function allow(string $key,int $bucket,int $limit): bool {
        $s=$this->pdo->prepare('INSERT INTO public.cgm_solo_vip_limits(bucket,rate_key,attempts) VALUES (?,?,1) ON CONFLICT(bucket,rate_key) DO UPDATE SET attempts=cgm_solo_vip_limits.attempts+1 WHERE cgm_solo_vip_limits.attempts < ? RETURNING attempts');
        $s->execute([$bucket,$key,$limit]);
        $allowed=$s->fetchColumn()!==false;
        // Opportunistic bounded retention. Failure denies the request safely.
        if (random_int(1,100)===1) {
            $d=$this->pdo->prepare('DELETE FROM public.cgm_solo_vip_limits WHERE bucket < ?'); $d->execute([$bucket-60]);
        }
        return $allowed;
    }
}
