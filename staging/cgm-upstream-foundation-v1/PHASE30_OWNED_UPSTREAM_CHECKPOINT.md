# CGM Phase 3.0 — Owned upstream admission, replay safety and provider boundary

**Checkpoint: 2026-10-28. Status: STAGING IMPLEMENTATION VERIFIED; independent PlayPark authentication and Garden session processing NOT available.**

## Completed (on `feat/phase30-owned-upstream-admission` only)

- Extended existing `staging/cgm-upstream-foundation-v1/` instead of rebuilding Phase40CD: original `src/ProviderHandoffGate.php` is preserved.
- Added `src/ExactCgmMembershipRepository.php` to adapt the current JPCG `cgm_membership_lookup(PDO, exactId, now)`. It appends `membership_login_id` after a trusted exact ACTIVE membership lookup because the existing CGM lookup does not emit that key. Requires `membership_active===true`; no client-supplied ACTIVE, trim/alias fallback or identity assumption.
- Added `src/PgAtomicAttemptStore.php` and `sql/001_cgm_upstream_admission_attempts.sql`. A PostgreSQL atomic `INSERT ... ON CONFLICT DO NOTHING RETURNING` consumes a globally unique attempt once across connections. Persist only a HMAC-SHA256 digest (no email/official identity/credential/session) and expiry, with a private stable 32+ byte key. Staging tests use a synthetic key. Key rotation needs an explicit migration or collision/replay-risk analysis.
- Added `src/AdmissionApi.php` and `public/admission.php`. HTTP accepts only a small, strict JSON claim/proof envelope to a **server-owned authorized provider verifier**. Shipped public route uses `UnconfiguredProviderVerifier`, `UnconfiguredAccountLinkRepository`, `UnconfiguredMembershipRepository`, `DenyOnlyAttemptStore`; it returns 503 without independently authenticated provider evidence. Synthetic positive policy result is `PRECONDITIONS_ATTESTED_ONLY` with `session_issued=false`, never `MGRelayResponse`.
- Existing legacy `public/session.php` under this staged root deliberately **returns 503** without reading password-bearing `?data=`. Staging `public/health.php` says no independent provider/session processor. `deploy/apache-path-only.conf` avoids query strings in Apache access logs, but does not control edge, CDN, or APM logging.

## Test evidence (all isolated, no live PlayPark)

- Full download package `CGM_Phase30_Owned_Upstream_Admission_2026-10-28.zip` contains the additional complete 45-case local policy suite, 15-case local HTTP test, complete source, PostgreSQL migration, CI test, staging Dockerfile and SHA manifest.
- Local **45/45** PHP admission tests PASS; loopback-only **15/15** HTTP checks PASS.
- GitHub Actions run **40710598149**: .NET not involved; PHP 8.3 syntax checks, genuine isolated PostgreSQL 16 **13/13** tests, and HTTP **15/15** checks all succeeded. Tests use disposable CI credentials and two separate PDO database connections; no Render/Neon DB was accessed.
- CI URL: https://github.com/jpmiantozaki/JPCG/actions/runs/40710598149
- This is a **server-side CGM-owned admission policy**. It is not an installable APK patch, a fully compatible ANLGarden 1026 intermediary, a PlayPark login, a VIP entitlement override, a genuine CCU/GameServer/Garden session, or a packet encryptor/signer.

## Open dependency and next phase

**Phase 3.1** needs a documented, authorized official provider interface that proves the same original PlayPark account and verifies/returns legitimate game and Garden sessions, including any original intermediary entitlement. Known ports (`18805`, `18806`, `20840`), original Unity response shapes, membership ACTIVE, or copied historical packets do not grant this permission or supply the missing server-side protocol. The official PlayPark Audition policy warns that unauthorized modified clients and third-party game programs may be sanctioned; obtain appropriate provider/operator authorization before routing real accounts.

Current JPCG production `public/session.php` remains the existing forwarding bridge. **Do not** deploy the staging service over it or route real account credentials to the staging `/session.php` endpoint. Preserve official authentication and deny any request lacking a trusted provider result. Phase2.9A already verified original account normalization; don't repeat native mapping or Garden viewer work.

JPCG `main`, current Render/Neon configuration, the original APK, experimental Nox, and rollback Nox `127.0.0.1:62030` were not changed.
