-- Retain newly issued Solo codes for authenticated admins only.
-- Preserve all existing rows, including older codes with an unknown plaintext.
-- Apply after 001 and 002. No legacy mobile tables are changed.
BEGIN;
DO $migration$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conrelid='public.cgm_solo_vip_codes'::regclass AND conname='cgm_solo_full_code_integrity') THEN
        ALTER TABLE public.cgm_solo_vip_codes ADD CONSTRAINT cgm_solo_full_code_integrity CHECK (
            code_full IS NULL OR (
                code_full ~ '^MGANG-SOLO-[A-F0-9]{32}$'
                AND encode(sha256(convert_to(code_full,'UTF8')),'hex')=code_hash
            )
        );
    END IF;
END;
$migration$;
ALTER TABLE public.cgm_solo_vip_codes DROP CONSTRAINT IF EXISTS cgm_solo_full_code_consumed;
COMMIT;
