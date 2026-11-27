-- Solo only. Existing legacy mobile codes and sessions are not changed.
BEGIN;
ALTER TABLE public.cgm_solo_vip_codes ALTER COLUMN email DROP NOT NULL;
ALTER TABLE public.cgm_solo_vip_codes ADD COLUMN IF NOT EXISTS code_full text;
DO $migration$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conrelid='public.cgm_solo_vip_codes'::regclass AND conname='cgm_solo_claim_has_email') THEN
        ALTER TABLE public.cgm_solo_vip_codes ADD CONSTRAINT cgm_solo_claim_has_email CHECK (redeemed_at IS NULL OR email IS NOT NULL);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conrelid='public.cgm_solo_vip_codes'::regclass AND conname='cgm_solo_full_code_consumed') THEN
        ALTER TABLE public.cgm_solo_vip_codes ADD CONSTRAINT cgm_solo_full_code_consumed CHECK (code_full IS NULL OR (redeemed_at IS NOT NULL AND code_full ~ '^MGANG-SOLO-[A-F0-9]{32}$'));
    END IF;
END;
$migration$;
COMMIT;
