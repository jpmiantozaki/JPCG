# Phase 40AK — Original JNI gate + recovered HTTPS membership transport (isolated)

**Date:** 2026-10-21. **Status:** Source-integrity checks, Java host compilation, 27 original JNI host tests, 78 Phase40AE host tests, and 10 new parser tests PASS. **Windows APK build and on-device HTTPS request are NOT TESTED.** Original CGM client, Unity callback and PlayPark/ANLGarden servers unchanged.

## Prior Phase40AJ runtime evidence

User-provided screenshot (after button press) displays `ANDROID ART: PHASE 40AJ JNI POLICY PASS: 27/27 original JNI assertions using this runtime.` Proven on disposable Nox62032, with synthetic fixtures. This does not validate any actual membership HTTP response or Unity CONFIRM callback.

## Phase40AK test package

Conversation artifact: `CGM_Phase40AK_HTTPS_Preflight_Android.zip`; SHA256 `9833df584f79ae6b3250a5be9ee540b8f8f5d1cf6c77c21395bb9317b627d890`.

The project reuses SHA-pinned **unaltered** Phase40AH C/JNI/Java native gate and Phase40AE Java `EmailProtocol`, `HttpsEmailTransport`, `EmailMembershipGate`. Adds a separate Android-only Activity (package `com.cgm.authfirst.aktest`) and 10 additional parser fixtures. Standard platform TLS, HTTPS-only POST, request-id validation, no redirects/cookies, response max 4096 bytes, 5-second HTTP timeout. The Android INTERNET permission applies only to this isolated test app.

The user manually clicks `RUN 40AK JNI + HTTPS TEST`. It exercises the 27 synthetic original-gate JNI checks and 10 strict protocol fixtures, then sends exactly one **synthetic random** `phase40ak-<nonce>@example.invalid` lookup to owned CGM endpoint `https://jpcg.onrender.com/authfirst.php`, using its existing `cgm-email-membership-v1` protocol. For an expected INACTIVE response, feeds INACTIVE into the original native gate and verifies denied/no continuation. For TLS/HTTP/timeout/ERROR, fail closed but **do not claim HTTPS integration PASS**. Unexpected ACTIVE is flagged and not converted into native authorization.

**Never calls** native `take()`, game `Login()`, official PlayPark authentication, Garden sessions, upstream VIP, or uses real credentials. Phase40AF original inner CONFIRM path 4300 -> `Login()` target 4610 is untouched.

## Tests here

- Native Java JNI host source suite: 27/27 PASS.
- Recovered Phase40AE source suite: 62+16 = 78/78 PASS.
- Additional Phase40AK strict contract fixtures: 10/10 PASS on JVM.
- Java syntactic compilation using Android API stubs: PASS.
- Source-sha and package/manifest/endpoint static checks: PASS.
- ZIP integrity: PASS.
- **No Windows NDK APK build, APK install, Android HTTPS fetch, or live CGM membership state proof performed**.

## One-command user testing

Extract under `D:\CGMTEST\CGM_Phase40AK_HTTPS_Preflight_Android`, then run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AK_HTTPS_Preflight_Android\scripts\run_phase40ak_full.ps1'
```

This builds, hash-checks and signs the separate APK, checks Nox62032 readiness, installs/launches only to `127.0.0.1:62032`, no protected `62030/62031` targets. The HTTP request occurs only after the button press. Wait 10–15 seconds, send final screenshot or any encountered PowerShell error.

A negative-path PASS would prove only restored native JNI + strict HTTP response parsing + server INACTIVE handling + fail-closed integration in an isolated Android app, **not** the real Unity login callback or CGM ACTIVE access to Garden.

## Nox62032 runtime outcome — confirmed PASS (user screenshots, 2026-10-21)

User ran `scripts/run_phase40ak_full.ps1` on Windows, with:
- Automatic ARM64 APK build PASS, SHA256 `67210A07D897BD5086FA245640580AFF022F5132F31A78CFE2C387E712B07FBC`, package `com.cgm.authfirst.aktest`, signature verified.
- ADB installation on disposable `127.0.0.1:62032`: `Success`. Activity launch `cmp=com.cgm.authfirst.aktest/.MainActivity`: succeeded.
- Screenshot 1: `OFFLINE JNI: 27/27 PASS`, `OFFLINE PROTOCOL: 10 PASS`, `HTTPS CHECKING: synthetic email only, no password or session...`
- Screenshot 2:
  - `ORIGINAL PHASE40AH JNI: 27/27 PASS`
  - `RECOVERED PHASE40AE PROTOCOL FIXTURES: 10/10 PASS`
  - `HTTPS RESPONSE: INACTIVE (expected for new synthetic ID)`
  - `NATIVE FAIL-CLOSED: PASS (denied/cancelled, no continuation)`
  - `PHASE 40AK NEGATIVE-PATH RUNTIME PASS`
  - `Original Unity CONFIRM and LIVE ACTIVE path NOT tested.`

**Interpretation:** The isolated Android APK can invoke original native gate, make a real HTTPS request with a synthetic email to the CGM-owned `authfirst.php` and deny continuation on a server INACTIVE result. This does **not** validate real ACTIVE membership, trusted official identity, app integration, ANLGarden upstream VIP, or PlayPark/Game/Garden authorization. Future work should concentrate on a verified original-client Unity/Android load point and callback binding, preserving the original login and server decisions, not repeat synthetic negative-path tests.
