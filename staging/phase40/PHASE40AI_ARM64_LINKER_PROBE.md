# Phase 40AI — NDK 27.2 verified and ARM64 linker/JNI probe prepared

**Date:** 2026-10-21. **Status:** SOURCE-ONLY ISOLATED PROBE; static safety validation passed; native Windows NDK compilation and Android/Nox runtime **not yet executed**.

User PowerShell output identifies official Android NDK installation under `C:\Users\Admin\AppData\Local\Android\Sdk\ndk\27.2.12479018`. A separately pasted `else` was interpreted as a command and failed; this is only PowerShell block syntax, not an NDK error. The preceding ZIP search showed no matching Phase40AH archive entry in the visible output; source remains unavailable.

The read-only Nox62032 ADB ABI preflight showed Android API 28, `x86_64,x86,arm64-v8a,armeabi-v7a,armeabi`, and `ro.dalvik.vm.native.bridge=0`. ARM64 advertised does NOT prove actual load.

## Follow-up test artifact

`CGM_Phase40AI_ARM64_Linker_Probe.zip` SHA256 `9a381435794608982eeec9ff8a253d3794a8db3733b4517efdf8ecc393567566`, 11 text-only source files. This extends the existing isolated Phase40AI shell but uses **different** test-only native name `libcgm_abi_probe.so` and **different** app ID `com.cgm.authfirst.abi_probe`. It is **not** the missing actual Phase40AH membership library.

PowerShell NDK compilation script `scripts/build_arm64_probe.ps1` uses official `aarch64-linux-android28-clang.cmd` and outputs only `app/src/main/jniLibs/arm64-v8a/libcgm_abi_probe.so`; no ADB, device, CGM/backend/game, installs, credentials or other network actions. C source exports a trivial `JNI_OnLoad` and JNI `nativeProbeAbi()` that returns fixed marker `64028`; isolated Java button tries `System.loadLibrary("cgm_abi_probe")` followed by the dummy JNI call. APK uses only arm64-v8a ABI and no Android permissions. Android Studio project requires JDK17, SDK35, AGP 8.6.1/Gradle 8.7.

## Verified here

- Python static safety check found isolated app ID, ABI-only packaging, no manifest permissions, correct loader + dummy JNI call: **STATIC PASS**.
- Script correctly reports missing library as **NOT BUILT** (exit code 2), which is expected until the user's Windows NDK is run.
- ZIP CRC complete: **PASS**.
- Native compilation on the user's NDK: **NOT RUN**.
- Actual ARM64 ELF Android runtime / JNI execution on Nox62032: **NOT RUN**.

## Next action

On the user's Windows machine, extract to `D:\CGMTEST\CGM_Phase40AI_ARM64_Linker_Probe`, then run:

```powershell
cd 'D:\CGMTEST\CGM_Phase40AI_ARM64_Linker_Probe'
powershell -NoProfile -ExecutionPolicy Bypass -File '.\scripts\build_arm64_probe.ps1'
py '.\scripts\verify_layout.py'
```

Only after ELF64 AArch64 static verification, build the **separate non-networked test APK** (NOT original CGM), inspect its APK ID/ABI, and consider test install on disposable `127.0.0.1:62032` only. If ARM64 load works, that proves device ARM64/JNI capability, **not** an executable CGM membership gate or verified Unity callback. The actual Phase40AH JNI source must still be recovered/rebuilt and independently tested.

Preserve Phase40AF inner CONFIRM Unity PathID4300 -> `Login()` target PathID4610. No direct or fake VIP/official game server authorization, no session fabrication, no edits to CGM APK or rollback Nox62030/62031.

## User-side NDK build and static validation result (2026-10-21)

PowerShell on Windows in `D:\CGMTEST\CGM_Phase40AI_ARM64_Linker_Probe` ran `scripts/build_arm64_probe.ps1`, with reported output:

- Compiled `app/src/main/jniLibs/arm64-v8a/libcgm_abi_probe.so` targeting Android API 28.
- SHA256: `496cc4072236f070737ccf57fc73497d39e599e678eb009c40c4ec7509d6e36a`.
- `py scripts/verify_layout.py` reported `STATIC PASS` and `ELF PASS`, hash equal to build output.
- **Not** yet built as APK, installed on Nox62032, or exercised through ART/JNI. Test C library is a harmless dummy probe, not Phase40AH membership gate.

**Next gate:** Build standalone debug APK (app ID `com.cgm.authfirst.abi_probe`), inspect APK contents and declared ABI `arm64-v8a` before a carefully targeted install on disposable `127.0.0.1:62032`. Do not use 62030 or 62031. Do not modify the original CGM/Unity CONFIRM button.
