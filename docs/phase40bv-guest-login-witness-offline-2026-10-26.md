# Phase 40BV — Live original Login method-pointer witness (offline-prepared)

**Checkpoint date:** 2026-10-26  
**Status:** New diagnostic source package verified **offline only**; not built, signed, installed or executed on Nox in this checkpoint. Previous **Phase40BP signed APK successfully restored and exact-hash verified on experimental 62033**.

## Why 40BV exists

Prior Phase40BU changed exactly 4 bytes at original ARM64 RVA `0x6A8868` (`BL SendLogin` -> `B Login epilogue`) in the signed APK. The user verified the Phase40BU APK was installed and an owned path-only fixture unexpectedly recorded one local `/session.php` request. It remains unknown whether the runtime used that modified instruction, another execution route, or a translated-code alias/cache. Native packet contents and official authentication were never captured or tested.

**ABI correction:** Phase40BP's in-APK CGM VIP JNI library only exists at `lib/arm64-v8a/libcgm_authfirst_gate.so`, yet the real app successfully logged `CGM_VIP_ORIGINAL_CONFIRM_ENABLED` through that native setter. Thus an ARM32-only runtime explanation of the earlier 40BU result is unlikely. The precise guest code used for `Login()` nevertheless needs runtime attestation. Android NativeBridge/guest mappings may have multiple aliases; this is a hypothesis, not a proven cause.

## What Phase40BV provides

A small source-only artifact `CGM_Phase40BV_Guest_Login_Witness.zip`; SHA-256 `1c9c10778c2534c9542f9b656afa4d3bc53c4eac52bdcdfe708c72151282025c` (**26,682 bytes**). No original APK or proprietary native binary included.

It **reuses the verified Phase40BP Unity-loop Java/JNI gate**, and adds three passive native readers:

- `nativeLoginCodeWitness()`: resolve the actual managed `LoginServerManager.Login()` MethodInfo methodPointer; verify it belongs to the expected guest `libil2cpp.so` at RVA `0x6A86E8`; read the expected entry instruction and classify the instruction at `0x6A8868`. Classifications distinguish original BL, test DENY branch, missing/mismatched method pointer, unreadable/unexpected bytes, and different guest-module alias.
- `nativeLoginProgressWitness()`: read original manager `isLoginProcess`, no setter.
- `nativeLoginCounterWitness()`: read current `DeathToB.Session.packetCount` field via IL2CPP runtime field access, with an internal ephemeral session-generation label. **Never call `Session.get_GetPacketCounter()` because it mutates the value.**

Events are coarse labels only: `LOGIN_METHOD_DENY_BRANCH_GUEST_BYTES`, `LOGIN_METHOD_ORIGINAL_GUEST_BYTES`, `LOGIN_METHOD_POINTER_MISMATCH_STOP`, `LOGIN_PROGRESS_SET/CLEAR`, `LOGIN_SESSION_COUNTER_ADVANCED_SAME_SESSION`, etc. No account ID, raw pointer, password, packet, relay URI, token or official response is logged.

**Important:** The observer is read-only, but this test-only APK also preserves the exact Phase40BU **unconditional DENY** one-instruction branch to probe that case. It is **not** an active conditional CGM sender guard and must be restored to Phase40BP afterward.

## Offline verification actually performed

- Original native SHA-256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8` checked.
- Exact four-byte ARM64 patch constructed from original ELF; no other ELF bytes changed.
- 10/10 targeted Python tests passed, including a synthetic ZIP payload-parity test: all original APK entries remain unchanged **except** `classes2.dex`, `lib/arm64-v8a/libcgm_authfirst_gate.so`, and patched `lib/arm64-v8a/libil2cpp.so`.
- 13/13 previously established CGM membership-response decision tests passed with the updated Java.
- C strict host/JNI syntax and Java 8-source compilation passed; Python code compilation passed.
- New file checksums, archive CRC and ZIP manifest verified.
- **Not verified:** Actual Windows Android NDK and D8 build, test-key signing, Nox installation, guest method-pointer reads, NativeBridge alias behavior, direct `Login()` execution, game authentication, Garden access.

## One bounded next check, no credentials

Build/install from known-good Phase40BP **only if currently exact installed** on **62033**, then launch the test APK and observe filtered `CGMPhase40BV` logcat tags **before pressing CONFIRM**. If live code classifies as `DENY_BRANCH`, report that marker and decide whether a separate one-shot isolated click test is warranted. If `ORIGINAL_GUEST_BYTES`, `POINTER_MISMATCH` or `UNVERIFIED` occurs, stop; do not submit credentials or relax checks. Use the bundled exact-hash restore script to return to Phase40BP. This first check needs no PlayPark account, original password or game session.

This method-pointer memory observation can show guest bytes **but not that the method was executed**. It is a prerequisite for a later execution-correlation test; one `/session.php` request alone remains insufficient.

**Boundaries:** no production or default-branch changes, no official traffic, no backend mutation, no host-side Frida attach, no APK install in this checkpoint, no device `62030` access. Legacy Facebook login is unsupported; current focus is ordinary PlayPark CONFIRM. Independent authentic game-session upstream remains unresolved.
