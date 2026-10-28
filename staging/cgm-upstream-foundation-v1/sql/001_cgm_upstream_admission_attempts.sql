-- STAGING ONLY. Apply to a dedicated upstream PostgreSQL test database;
-- no changes have been made to live Neon/Render.
CREATE TABLE IF NOT EXISTS cgm_upstream_admission_attempts (
  attempt_digest CHAR(64) PRIMARY KEY,
  valid_until BIGINT NOT NULL CHECK (valid_until > 0),
  created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);
CREATE INDEX IF NOT EXISTS cgm_upstream_attempt_expiration
  ON cgm_upstream_admission_attempts (valid_until);
-- Store only irreversible keyed HMACs, not email, login IDs, claims or session values.
