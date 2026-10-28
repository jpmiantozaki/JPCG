# CGM Upstream v3 — Stage-0 and Stage-1 Relay Continuity

**Status (2026-10-23): Local source complete; synthetic tests PASS; no live deployment or APK changes.**

**Artifact:** `CGM_Upstream_Session_Continuity_v3.zip`, SHA-256 `3a43fb744a76e400b153a2622020df4a4933ebb277cdeda4fa9b0c0d53ce5e25`, 10 ZIP entries, ZIP CRC PASS. Package supplied in the project conversation under /mnt/data.

**Scope:** User explicitly chose upstream-only implementation; stop further APK/IL2CPP experimentation. The existing Phase v2 local tests were user-confirmed 32/32 PASS on Windows, but v2 refused stage-1 relay continuity. This v3 implements PHP stage-0 VIP validation, configured compatible upstream HTTPS request/reply pass-through, response-shape inspection without changing upstream ciphertext, keyed short-lived session fingerprint persistence, and stage-1 admission only for previously observed relay sessions.

**Testing (completed locally, no network):**
- PHP lint all staged PHP files PASS.
- `tests/run.php`: 37/37 synthetic cases PASS.
- `tests/integration_cli.py`: 9/9 across separate PHP processes and mocked existing-upstream endpoint PASS.
- Stage 0 ACTIVE -> request forwarded unchanged, upstream protected result preserved; INACTIVE/EXPIRED -> encrypted VIP error; upstream rejection -> original rejection preserved; invalid response, unknown stage1 token, expired 120s test-only admission, malformed packets and outages -> fail closed; state file contains keyed HMAC digest, not plaintext relay token.

**Staged files (not deployed):**
- `patch/public/_cgm_stage0_vip_gate.php`
- `patch/public/_cgm_session_continuity_v3.php`
- `patch/public/_cgm_v3_file_state.php`
- `patch/public/_cgm_v3_https_transport.php`
- `patch/public/session.php`
- `tests/run.php`, `tests/integration_cli.py`, README, manifest, offline test log.

**Important limitations:**
1. The trusted configured upstream (if present) must produce genuine provider-compatible `MGRelayResponse.data` and `.session` values; the client response schema doesn't supply server-issued state. This code **never forges error=false**. No MOD backend private session issuer has been recovered, and this v3 is **not** a stand-alone MOD-equivalent session service.
2. If the existing upstream still requires BASE VIP, it may continue rejecting independent CGM members. That condition is preserved; do not turn upstream `error=true` into success.
3. HMAC state file is single-host **staging only**, not production distributed/replay-safe state; 120-second limited lifetime. The original relay uses credential-bearing GET queries; review/redact default access logging and authorized data handling before any live accounts are routed through an upstream.
4. No original Android APK changed, Nox touched, external authentication requests made, production Render/Cloudflare code modified, or real game/Garden login tested.

**Next actual product dependency:** A real independent backend session processor capable of obtaining legitimate login/CCU/game/Garden responses from the actual supported game servers. It cannot be synthesized from the parsed request/response field names. We should not create more repetitive membership/JNI tests or claim full functionality until a genuine provider-compatible server path is available.


## Windows verification supplied by user — 2026-10-23

User executed locally:

`& 'D:\CGMTEST\PHP\php.exe' 'D:\CGMTEST\CGM_Upstream_Session_Continuity_v3\tests\run.php'`

Actual terminal output ended `ALL 37/37 PASS`. Observed checks include original protected request and exact upstream encrypted response preservation, stage-0 CGM ACTIVE admission, matching stage-1 relay continuation, unknown or expired session rejection, INACTIVE/EXPIRED VIP denial, upstream rejection preservation, membership/processor outage fail-closed behavior, HMAC-only staging state, and no synthesized game-session success.

This adds user-confirmed Windows local PHP test execution to the prior assistant-local 37/37 and 9/9 synthetic tests. **No real MOD/BASE-independent provider session processing, game login, CCU or Garden entry was demonstrated.** The new test output does not show deployed JPCG public routes or a changed APK. Next engineering decision must address origin/provider-compatible session processing, not repeat the same PHP policy or Unity runtime checks.
