-- New codes: ACGM followed by 8 unambiguous uppercase alphanumeric characters.
-- Existing MGANG-SOLO codes and all existing memberships remain valid.
-- Apply after 001, 002 and 003. No legacy Mobile tables are touched.
BEGIN;
DO $migration$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conrelid='public.cgm_solo_vip_codes'::regclass AND conname='cgm_solo_dual_code_integrity') THEN
        ALTER TABLE public.cgm_solo_vip_codes ADD CONSTRAINT cgm_solo_dual_code_integrity CHECK (
            code_full IS NULL OR (
                code_full ~ '^(ACGM[2-9A-HJ-NP-Z]{8}|MGANG-SOLO-[A-F0-9]{32})$'
                AND encode(sha256(convert_to(code_full,'UTF8')),'hex')=code_hash
            )
        );
    END IF;
END;
$migration$;
ALTER TABLE public.cgm_solo_vip_codes DROP CONSTRAINT IF EXISTS cgm_solo_full_code_integrity;
COMMIT;
