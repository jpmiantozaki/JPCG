# Phase 40AI — Nox62032 read-only ABI readiness follow-up

**Date:** 2026-10-21. **Status:** ADB preflight helper authored and statically checked, but NOT executed on Windows, PowerShell, Nox, or Android. No game APK, live credentials, upstream service, production backend, or emulator modified.

## New artifact
Conversation package `CGM_Phase40AI_Android_Loader_With_Readonly_Preflight.zip` SHA256 `157f68a8b2d569eafd8f982ebc85760938e3a4c7e4448941c03d302278034f57`. Contains the 9-file Phase40AI Java loader scaffold with its README expanded, plus `scripts/check_nox62032_readonly.ps1` (10 files). ZIP integrity and script static safety assertions passed locally.

### What the script does
- Refuses every device serial except `127.0.0.1:62032`, deliberately never addresses protected 62030/62031.
- Requires the target already appear in `adb devices -l` as `device` (does **not** invoke `adb connect`).
- Reads `ro.build.version.sdk`, `ro.product.cpu.abilist`, `ro.product.cpu.abi`, `ro.dalvik.vm.native.bridge`, `ro.product.model`, `ro.zygote` via read-only `adb -s 127.0.0.1:62032 shell getprop`.
- Saves a local JSON device-readiness report; never installs, launches, pushes, remounts, runs as root, collects account secrets, or queries network APIs.
- Calls an ABI property `ARM64_ADVERTISED_RUNTIME_LOAD_NOT_PROVEN` only when `arm64-v8a` is listed. Otherwise a claimed native bridge is classified as translation *unverified*. No load PASS is claimed in any state.

### How to run on user's Windows machine
After extracting ZIP to `D:\CGMTEST`, open PowerShell:

```powershell
cd 'D:\CGMTEST\CGM_Phase40AI_Isolated_Android_Loader'
powershell -NoProfile -ExecutionPolicy Bypass -File '.\scripts\check_nox62032_readonly.ps1'
```

Use `-AdbPath` if the default `D:\Program Files\Nox\bin\nox_adb.exe` is missing.

The next evidence is the generated `scripts/Phase40AI_Nox62032_Device_Report.json`. Confirm whether the device **advertises** arm64-v8a before considering a separate harness installation, and independently prove any translation behavior. Do not install ARM64-only libraries on incompatible devices.

## Still blocked
- Actual Phase40AH source/`.so` absent from this follow-up environment; original JNI candidate was cross-compiled without the official Android NDK.
- No local Android SDK, NDK, Gradle, Android device/ADB, or PowerShell runtime available here; the new PowerShell file has **not** been runtime-validated.
- No Android ART load result, no verified JNI method calls, no Unity CONFIRM listener integration, no official authentication or Garden authorization result.
- Preserve Phase40AF inner CONFIRM PathID 4300 -> original `Login()` target PathID 4610. Do not disable that listener without an independently verified replacement and authorization boundary.
