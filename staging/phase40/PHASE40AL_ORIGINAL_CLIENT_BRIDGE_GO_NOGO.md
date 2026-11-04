# Phase 40AL — original CGM Unity/Android executable bridge boundary (read-only checkpoint)

**Date:** 2026-10-22. **Status:** Static reference audit of archived original CGM APK and preservation of 40AF serialized callback binding. No APK changes, login attempt, Nox action, live account use, or upstream Game/Garden authorization.

## Evidence

- The recovered archived `CGM-62031-Local-SIGNED.apk` SHA-256 independently verified `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`.
- Its `classes.dex` is 2,997,228 bytes. Direct byte search found **zero** occurrences of `cgm_authfirst_gate`, `com/cgm/authfirst`, `com.cgm.authfirst`, `authfirst`, or `cgm`. This supports the previous Phase40AH finding: the original APK does not already reference our generated JNI loader. String absence cannot formally rule out dynamically constructed reflection/load paths.
- Existing DEX contains `UnityPlayerActivity`, `UnitySendMessage`, and a generic `loadLibrary` reference; those generic references are part of Unity/Android infrastructure, **not proof** of a callable CGM membership bridge.
- ARM64 native library hashes:
  - `libil2cpp.so`: `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`
  - `libmain.so`: `3b5cd26a7734284ebe1a4b38ebce79dd5a9be8b4500abee2ff117a2072fd2c3d`
  - `libunity.so`: `ff1625226da11b05792b2efba9f1ca2358f7ea6506b4d41e6b227c84a70978a4`
- Phase40AF prior verified serialized scene proof, same APK hash: inner CONFIRM PathID4300 -> `Login()` target PathID4610, listener RuntimeOnly=2. A test-only extracted scene copy with call state Off (0) is reversible, **but there is no verified replacement executable callback**.
- Phase40AJ user screenshot: actual original Phase40AH JNI gate 27/27 assertions PASS on isolated Android ART, disposable 62032.
- Phase40AK user screenshots: 27/27 JNI, 10/10 recovered protocol fixture tests PASS; actual synthetic HTTPS lookup returned INACTIVE and native gate denied/cancelled continuation; negative-path runtime PASS.

## Decision

**GO:** Original Phase40AH gate and recovered HTTPS transport execute in the **separate** isolated Android test APK, with proper INACTIVE fail-closed behavior.

**NO GO:** Do not simply copy `libcgm_authfirst_gate.so` into the third-party CGM APK and disable the original CONFIRM button. The archived CGM DEX has no verified loader for that library, and the Unity scene contains no executable gate callback or original-login continuation. The previous reflective pointer ownership route was unresolved and must not be assumed fixed.

**Next engineering requirement:** Identify and prove an authorized, actual Unity-owner-thread callback or app-level integration point in a separately signed disposable staging copy that can (1) consume pre-existing email field, (2) call verified HTTPS membership query, (3) allow `Login()` exactly once only for fresh ACTIVE, (4) keep original login, relay and Garden server responses authoritative, (5) remain fail-closed for errors. Source-based integration into a matching Unity project is preferable if available; otherwise an independently validated native/IL2CPP extension requires careful lifecycle, GC safety, integrity and state audits **before** any install. No unsupported native patch or fabricated session should be presented as done.

This Phase40AL report is a static readiness decision and a bridge blocker statement, **not** a working CGM APK or a new runtime test. Protect Nox62030/62031; do not modify production.
