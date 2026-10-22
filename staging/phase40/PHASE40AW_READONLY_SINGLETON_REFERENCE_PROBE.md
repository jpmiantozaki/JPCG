# Phase 40AW — Read-only live LoginServerManager.Instance static field snapshot

**Date:** 2026-10-22. **Status:** Source prepared + host Java/C syntax and static safety checks PASS, no Windows Android build or runtime performed. Separate test-signed staging client install on disposable Nox62033 only.

## Current verified state

User-confirmed Phase40AV / Phase40AU runtime: SHA `CC80B36DDA5ADB9ECA64633F1C70C16A6C7C15B4F5506A49F37411D5FE76F39A` installed and hash-verified on disposable Nox62033; original Unity Activity launched. JNI lib loaded, `LoginServerManager.Login()` method metadata found at 12s and 24s (`STATUS=100`) without invoking game methods. No original Unity CONFIRM replacement or login auth success demonstrated.

User-supplied Il2CppDumper `dump.cs` for `LoginServerManager` TypeDef3318 reveals `private static LoginServerManager <Instance>k__BackingField; //0x0`, static `Instance` property and `Awake`/`OnDestroy` methods. This motivates a read-only static-field snapshot, not method invocation.

## Artifact

`CGM_Phase40AW_ReadOnly_Login_Instance_Probe.zip`, SHA256 `9fdca08d4fd029174cc8c3515e811376123aebf22d7cf66c245fef396ed9b669` (12 entries, ZIP integrity PASS). Built on Phase40AU source, preserving the original Phase40AH C policy/JNI sources SHA-exact. The only new native code is `phase40aw_readonly_instance.c`:
- Dynamically attaches to already-loaded `libil2cpp.so` and locates `Assembly-CSharp`/class `LoginServerManager` and metadata for its method `Login()`, **without invoking**.
- Locates static `<Instance>k__BackingField`, verifies static flag, reads its managed *reference value only* into a local pointer using `il2cpp_field_static_get_value`, and logs merely non-null/NULL at 12/24 seconds. Does not dereference, pin, log pointer, access `txtUsername` or `txtPassword`, call managed property getters, use Unity API on the wrong thread, or send any network/PlayPark/VIP/Garden request.
- Reuses prior same-key test-only signer with fingerprint `6fef57b71ce2f70af9cca1df6578620462ec55065a9151f78d0194386ebf25ed`; guards exact installed Phase40AU hash before update, tests ZIP-preserving 167 original entries, verifies new signed APK hash after update.
- Fixes Phase40AU Nox ADB stderr `Success` pitfall: captures both stderr/stdout and checks exit code plus exact *installed APK bytes*, not an unreliable stdout marker. Only ADB serial `127.0.0.1:62033`. Optional guarded rollback to exact Phase40AU signed build. Does not touch Nox62030/62031/62032, does not click LOGIN or alter original CONFIRM listener.

## Source/testing results in this workspace

- C11 native syntax `clang -Wall -Wextra -Werror -fsyntax-only` with JNI and Android Log headers: PASS.
- Java 8 preloader compilation against minimal Android API stubs: PASS.
- Original archived CGM ARM64 ELF exports checked directly with `readelf`: **11/11** target IL2CPP symbols exist, including `il2cpp_class_get_field_from_name`, `il2cpp_field_get_flags`, `il2cpp_field_static_get_value`.
- Unmodified native gate+JNI source SHA equals prior 40AU: PASS.
- Python ZIP signed-content verifier synthetic fixture: PASS, and intentional original entry tampering was rejected: PASS.
- PowerShell/Nox target safety static regression: PASS.
- ZIP CRC: PASS.
- **No Android NDK build, signing, Nox update, or actual singleton read yet performed.** Native component pointer presence would be a transient snapshot only, not GC lifetime, safe access, Unity owner-thread proof, callback gating, or official server access.

## Single Windows command for user

Extract into `D:\CGMTEST\CGM_Phase40AW_ReadOnly_Login_Instance_Probe`, leaving the Phase40AU installed test app on disposable Nox62033, and run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AW_ReadOnly_Login_Instance_Probe\scripts\run_phase40aw_full.ps1'
```

Expected **only if true**: `PHASE40AW_PROBE_12SEC STATUS=110`, `PHASE40AW_PROBE_24SEC STATUS=110`, `READONLY_SINGLETON_SNAPSHOT_PASS`. If null: `STATUS=105`; preserve that result, don't force an instance. Logs and JSON in package `results/`. Optional rollback via `scripts/rollback_phase40aw_disposable_only.ps1` after collecting results.

**Safety:** Experimentally loading arbitrary modules into third-party Unity compiled app can crash. Only disposable isolated environment. Startup of original game may independently contact game servers; network-isolate if zero game traffic required. No credentials, no login, no callback modification, no session manipulation. Never assume CGM membership overrides third-party PlayPark/ANLGarden VIP/session decisions.
