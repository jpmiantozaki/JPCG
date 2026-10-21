# Phase 40AI — automatic local APK builder checkpoint

**Status (2026-10-21):** one-command Windows PowerShell APK build script authored and statically inspected. **No Windows packaging execution or Android runtime test has yet occurred** in this turn.

The user previously compiled `libcgm_abi_probe.so` successfully with NDK 27.2.12479018 (SHA-256 `496cc4072236f070737ccf57fc73497d39e599e678eb009c40c4ec7509d6e36a`) and ran `verify_layout.py` with STATIC PASS and ELF PASS.

A new conversation artifact `CGM_Phase40AI_OneCommand_APK_Builder.zip` (SHA-256 `92dd7299ddfc3e19320ce24111a1346407ff806b8c629e5865e24c097366d97e`) includes `scripts/build_probe_apk.ps1`. There is also a standalone copy `build_probe_apk.ps1` for dropping into the existing project scripts folder.

## Intended build process

The new PowerShell script locates local Android SDK platform, SDK Build Tools, JDK and NDK, compiles the harmless ARM64 JNI native probe, compiles Java bytecode, converts to DEX with D8, creates an isolated AAPT APK, inserts `classes.dex` and the sole `lib/arm64-v8a/libcgm_abi_probe.so`, zipaligns, generates local test-only debug keystore, signs and verifies via apksigner, checks package/ABI, and prints the APK SHA256. **No Gradle, Android Studio UI or network download is needed** if SDK tools are installed.

Expected output under local test project: `app/build/outputs/apk/debug/app-debug.apk`. Test package ID `com.cgm.authfirst.abi_probe`. The script contains no ADB, install, Nox process-control, CGM/PlayPark backend calls or original APK modification. It is a simple **build-only** script. It is not evidence that the real Phase40AH membership gate has been loaded.

**Next checkpoint:** user runs the builder on Windows and provides output. On a signed APK build PASS, inspect and consider a separate explicit install/test on disposable `127.0.0.1:62032` only. Protect Nox62030 and Nox62031. Maintain Phase40AF CONFIRM `4300` -> original `Login()` on `4610` unchanged; CGM ACTIVE does not bypass upstream VIP or official authorization.
