-- Generate ACGM plus ten decimal digits. Accept both previous Solo formats.
-- Preserve all existing rows. Apply after 001 through 004.
BEGIN;
DO $migration$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conrelid='public.cgm_solo_vip_codes'::regclass AND conname='cgm_solo_three_code_integrity') THEN
        ALTER TABLE public.cgm_solo_vip_codes ADD CONSTRAINT cgm_solo_three_code_integrity CHECK (
            code_full IS NULL OR (
                code_full ~ '^(ACGM[0-9]{10}|ACGM[2-9A-HJ-NP-Z]{8}|MGANG-SOLO-[A-F0-9]{32})$'
                AND encode(sha256(convert_to(code_full,'UTF8')),'hex')=code_hash
            )
        );
    END IF;
END;
$migration$;
ALTER TABLE public.cgm_solo_vip_codes DROP CONSTRAINT IF EXISTS cgm_solo_dual_code_integrity;
COMMIT;
