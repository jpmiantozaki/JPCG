# Phase 40AI — isolated Android ART loader preflight

**Date:** 2026-10-21. **Status:** Standalone Android Studio loader smoke-test project authored; local static checks passed. **Android runtime loading NOT tested.** No installed CGM APK, Nox, upstream servers, Render/Cloudflare production, or repository main branch modified.

## Preserved Phase40AF / 40AG / 40AH baseline

- Inner CONFIRM Unity Button Path ID 4300 has one original runtime listener calling `Login()` on LoginServerManager Path ID 4610. Phase40AF's one-byte listener-Off change was reversible only in an extracted scene copy, not in an executable APK.
- Phase40AG built a native ARM64 membership-policy shared library, with 34/34 offline native host fixture assertions.
- Phase40AH built Java/JNI interop, with 27/27 host JVM assertions, plus an ARM64 candidate ELF assembled without the official Android NDK. No Android ART loading or Unity IL2CPP event invocation has yet been demonstrated.
- The existing APK still has no proven DEX reference, manifest registration, or runtime loader for `libcgm_authfirst_gate.so`.

## New Phase40AI deliverable

Conversation artifact: `CGM_Phase40AI_Android_Loader_SmokeTest.zip`, SHA256 `fdadf0e2ec9f653c00bed8f4e32e8fcccb334e100af872fc86d9101dadbe3b14`.

The source-only 9-file Android Studio project provides:

1. Independent Java Activity that displays device ABI and attempts `System.loadLibrary("cgm_authfirst_gate")` only when its test button is clicked.
2. No network permission, CGM backend calls, game credentials, serialized Unity event patches, or CGM app package references.
3. A `verify_layout.py` preflight: guard against adding permissions/game package references, confirm intended load call; when a library is supplied, enforce ELF64 little-endian ARM64 and print its hash.
4. Reproducible instructions for Android Studio/JDK17/SDK35, official Android NDK native rebuild, and a **separately isolated** disposable device test.

## Tests executed here

- Static project checks: **PASS**.
- Library presence: **NOT READY** (the Phase40AH source/binary archive is not attached or present in the current environment); expected missing-library outcome, not a runtime failure.
- ZIP CRC integrity: **PASS**, 9 files.
- Actual Android compilation / Nox 62032 / JNI_OnLoad runtime result: **NOT TESTED**.

## GO/NO-GO

Only after supplying/rebuilding the real library and confirming ABI compatibility, test Android ART loading inside this **separate non-networked harness** on `127.0.0.1:62032`. Never touch rollback Nox instances `127.0.0.1:62030` or `127.0.0.1:62031`.

A load PASS demonstrates linker and JNI_OnLoad acceptance **only**. It does not imply native policy JNI calls, Android HTTPS transport, Unity main-thread invocation, a safe original `Login()` callback, CGM membership acceptance, ANLGarden VIP replacement, or official PlayPark/Garden session authorization. Do not disable the actual CONFIRM listener without a validated, authorized replacement. Do not override any independent upstream access decision.
