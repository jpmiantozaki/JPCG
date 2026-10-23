# CGM Identity Trust Boundary v1 — staging checkpoint (2026-10-24)

## Checked JPCG code
Reviewed `public/authfirst.php`, `public/verify.php`, `public/_authfirst_resolve.php`, `public/_cgm_membership.php`, `public/_common.php`, `public/redeemvip.php`, `public/session.php`, `public/_cgm_session_bridge.php` on `feat/phase40-staging-provider-interface`.

**Current behavior:** `authfirst.php` accepts a claimed identifier and correctly returns `game_authorized=false`; its membership lookup is not evidence of game-account ownership. `verify.php` similarly treats the legacy protected packet as a membership lookup, not a login proof. Existing bridge forwards HTTP queries and optional Cookie/Authorization headers to a configured third-party origin. Legacy redeem stores `code_full` and logs exception messages on failure; retention/log redaction should be audited separately.

## Staging-only code artifact
A conversation ZIP `CGM_Identity_Trust_Boundary_v1.zip` was produced with SHA256 `a96d207653fdd4f86b3a4e00e94869d193bf1de43a1227a180da9ad1435f2c33`. It contains:
- `patch/public/_cgm_bound_identity.php`: verifies a short-lived, separate trusted-attester HMAC assertion (exact issuer/audience, exact INPP. game subject, expiry and 128-bit one-time nonce), then performs **exact CGM membership lookup**, without any username-alias fallback;
- `patch/public/bound_membership.php`: separate optional membership-only server-to-server endpoint, disabled unless `CGM_BOUND_GATE_ENABLED=1`; always sets `game_authorized=false`, never modifies original routes;
- `patch/public/_cgm_safe_forward_headers.php`: standalone future cross-origin header filter that excludes Cookie, Authorization, admin credential headers; **not installed** in production bridge;
- `migrations/001_identity_nonce.sql`: atomic PostgreSQL nonce replay protection;
- `tests/run.php`, README and analysis.

**Offline validation:** PHP lint PASS, **38/38 synthetic tests PASS**, default-off endpoint preflight with zero output, ZIP CRC PASS. Windows command after extracting to D:\CGMTEST:
`& 'D:\CGMTEST\PHP\php.exe' 'D:\CGMTEST\CGM_Identity_Trust_Boundary_v1\tests\run.php'`.
These results were produced in the assistant container, **not yet user-run on Windows**.

## Trust restrictions and unresolved work
This package **does not issue signed assertions**, because CGM cannot independently authenticate an official game account based on user-supplied IDs or a SecurePacket checksum. A **separate legitimately trusted attester** must actually validate the subject before signing. Staging deployment additionally requires private edge access, secure signing-key lifecycle, isolated PostgreSQL migration and verified trusted-source configuration. HMAC alone only proves who signed, not that PlayPark verified identity. Existing public membership endpoints remain unchanged and are not automatically hardened by adding this separate path.

No APK, emulator, production PHP route or third-party game service modified or contacted. Original session.php independence still blocked on legitimate compatible packet generation, game-service acceptance and relay-session lifecycle. Do not fabricate authenticated packets or enable this route without a real attester.
