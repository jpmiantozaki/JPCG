<?php
declare(strict_types=1);
require_once __DIR__.'/_solo_vip_service.php';
// Uses existing JPCG db() connection (DATABASE_URL), with no new credentials.
// The existing helper may perform its original idempotent legacy schema checks.
final class SoloPgStore implements SoloStore {
    public function __construct(private PDO $pdo) {}
    public function issue(array $r): void {
        $s=$this->pdo->prepare('INSERT INTO public.cgm_solo_vip_codes(code_hash,code_hint,email,vip_days,created_at) VALUES (?,?,?,?,?)');
        $s->execute([$r['code_hash'],$r['code_hint'],$r['email'],$r['vip_days'],$r['created_at']]);
    }
    public function rows(string $email): array {
        $s=$this->pdo->prepare('SELECT redeemed_at,vip_expiry,revoked_at FROM public.cgm_solo_vip_codes WHERE email=?');
        $s->execute([$email]); return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    public function redeem(string $hash,string $email,int $now): ?int {
        // A single conditional write is atomic under concurrent redemption and revocation.
        // Expiry starts at redemption, never updates on replay. Email must match assignment.
        $s=$this->pdo->prepare('UPDATE public.cgm_solo_vip_codes SET redeemed_at=CAST(? AS bigint), vip_expiry=CAST(? AS bigint)+CAST(vip_days AS bigint)*86400 WHERE code_hash=? AND email=? AND redeemed_at IS NULL AND revoked_at IS NULL RETURNING vip_expiry');
        $s->execute([$now,$now,$hash,$email]);
        $expiry=$s->fetchColumn(); return $expiry===false?null:(int)$expiry;
    }
    public function revoke(string $hash,int $now): bool {
        $s=$this->pdo->prepare('UPDATE public.cgm_solo_vip_codes SET revoked_at=CAST(? AS bigint) WHERE code_hash=? AND revoked_at IS NULL');
        $s->execute([$now,$hash]); return $s->rowCount()===1;
    }
    public function recent(): array {
        return $this->pdo->query('SELECT code_hash AS code_id,code_hint,email,vip_days,created_at,redeemed_at,vip_expiry,revoked_at FROM public.cgm_solo_vip_codes ORDER BY created_at DESC,code_hash DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
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
