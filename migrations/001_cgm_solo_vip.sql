-- Apply on a disposable development database first. No changes to legacy codes/sessions.
BEGIN;
CREATE TABLE IF NOT EXISTS public.cgm_solo_vip_codes (
    code_hash text PRIMARY KEY CHECK (code_hash ~ '^[a-f0-9]{64}$'),
    code_hint text NOT NULL,
    email text COLLATE "C" NOT NULL CHECK (length(email) BETWEEN 3 AND 254),
    vip_days integer NOT NULL CHECK (vip_days BETWEEN 1 AND 3650),
    created_at bigint NOT NULL,
    redeemed_at bigint,
    vip_expiry bigint,
    revoked_at bigint,
    CHECK ((redeemed_at IS NULL AND vip_expiry IS NULL) OR
        (redeemed_at IS NOT NULL AND vip_expiry IS NOT NULL AND vip_expiry > redeemed_at))
);
CREATE INDEX IF NOT EXISTS cgm_solo_email_idx ON public.cgm_solo_vip_codes(email, vip_expiry DESC);
CREATE TABLE IF NOT EXISTS public.cgm_solo_vip_limits (
    bucket bigint NOT NULL,
    rate_key text NOT NULL,
    attempts integer NOT NULL CHECK (attempts > 0),
    PRIMARY KEY (bucket, rate_key)
);
COMMIT;
