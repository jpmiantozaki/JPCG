# Phase 40BX — Authorized session-admission contract (offline source checkpoint)

**Date:** 2026-10-26  
**Status:** Host-only source package validated; no backend route/deployment, no Nox/APK change.

## Existing staging facts

- `public/authfirst.php` and `public/_cgm_membership.php` provide CGM membership eligibility: ACTIVE does **not** mean official game authentication or Garden access.
- `public/session.php` delegates to `public/_cgm_session_bridge.php`, which forwards the existing relay request to a configured original HTTPS upstream. It does not issue an independently authorized game session or override upstream restrictions.
- Phase 40BV's user-witnessed original CONFIRM press on a test-only deny-branch APK, with same-process patched-code marker, produced a bounded synthetic local `config=1,session=0,other=0` probe. The exact signed Phase 40BP APK was restored and verified on disposable Nox62033. These are client-behavior findings, not official server authorization.

## Delivered source-only artifact

`CGM_Phase40BX_Authorized_Session_Adapter_Source_OFFLINE.zip`  
ZIP SHA-256 `33585f7553edcfdcf29f9d681862906f38254cfd23ee7fad1ecba44efbbd794e` (**9,004 bytes**). Includes `src/AdmissionGate.php`, `tests/offline_test.php`, `scripts/run_offline.ps1`, `README.md`, `VALIDATION.md`, and SHA manifest. No proprietary APK, provider session, account data, secret, or upstream packet is packaged.

The independent admission contract requires:

1. Server-side **CGM** authoritative membership lookup for the exact email, ACTIVE and unexpired. It must not trust a client-supplied ACTIVE boolean.
2. Separately **official-provider-verified** session for the **exact same account**. Official game authentication, Garden authorization and provider session expiry must all pass.
3. If either check is unavailable, expired or not authorized, fail closed.
4. A positive `AUTHORIZED_TO_PROCEED` is **only a decision** (`scope=CGM_AND_OFFICIAL_PROVIDER_ADMISSION_DECISION_ONLY`, `session_issued=false`). It does not construct or replay session packets, bypass VIP checks, or create a PlayPark/Garden session.

`UnconfiguredOfficialProviderVerifier` always denies; a real authorized verifier is a dependency **not supplied or invented** by this phase. Synthetic verifiers exist only within host tests, not the production library.

## Evidence

- PHP syntax for library and test: PASS.
- `php tests/offline_test.php`: **PASS 56 assertions**, including independent CGM and provider approval, fail-closed outages, account mismatch, expired sessions, unsupported/malformed inputs, no provider verification, and output privacy.
- Archive CRC PASS; `MANIFEST.sha256` PASS for every file after clean extraction.
- No external requests; no Nox62033 or rollback62030 access; no default branch/prod change.

## Required dependency for a real integration

Obtain a documented, permitted way for the actual provider to verify a game session and Garden access for an account. The provider may independently require VIP/other entitlements. Without that evidence, the safe adapter remains **not configured and denying**. The existing relay must not be mistaken for a provider-authentication API.

**Next safe engineering task:** once a real provider verification contract exists, integrate it server-side behind a trusted verifier, add a binding/replay store and tests. Keep client credentials and session materials out of diagnostic logs.