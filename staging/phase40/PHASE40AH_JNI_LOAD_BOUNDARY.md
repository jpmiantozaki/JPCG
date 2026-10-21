# Phase 40AH — CGM APK Java/native loading boundary

**Status:** Read-only original APK audit; Java JNI staging bridge built and host-tested. **No Android runtime or CGM login hook deployed.** Continuation of Phase40AF scene button and Phase40AG C native gate, not a new authentication provider.

## What was tested

- Target archived CGM APK: `CGM-62031-Local-SIGNED.apk` SHA256 `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`. Currently installed Nox62032 copy may differ in archive SHA; do not apply artifact offsets indiscriminately.
- Actual original APK contains only `libil2cpp.so`, `libmain.so`, `libunity.so` under ARM64; `readelf -d` confirms none has DT_NEEDED `libcgm_authfirst_gate.so`. Its DEX lacks `com.cgm.authfirst.bridge.NativeGate` and library-load string `cgm_authfirst_gate`. There is **no proven existing load path** for our new module.
- Added `NativeGate.java`, with `System.loadLibrary("cgm_authfirst_gate")` and owner-thread checks. Added JNI C wrapper `cgm_gate_jni.c` which calls the existing unchanged Phase40AG native `cgm_gate_begin/reply/take/cancel` functions.
- **27/27 genuine host JVM JNI assertions pass**, including library loading, actual Java->native function calls, changed and remembered email, ACTIVE once-only continuation, INACTIVE/expiry/timeouts, generation mismatches, cancellation and wrong-thread rejection.
- **34/34** original Phase40AG native fixture assertions pass again.
- Created **Android-target ARM64 ELF64** `libcgm_authfirst_gate_arm64.so`, SHA256 `7922f54735731e6f1a93125ab3787e8228e63c5da525ad3434125318873c4496`. Exports `JNI_OnLoad` and JNI entrypoints for begin/reply/take/cancel/state/abi; SONAME `libcgm_authfirst_gate.so`; no DT_NEEDED dependencies. Cross-built using Clang with JDK JNI headers and a build-only `stdio.h` inclusion shim because the Android NDK is missing, so **Android ART runtime loading has NOT been tested**. Rebuild with official NDK headers before real use.

## Phase 40AF is still the client wiring baseline

Inner CONFIRM Unity Button Path ID `4300` has one persistent `Login()` callback on `LoginServerManager` Path ID `4610`, state `2` Runtime Only. Phase40AF proved a one-byte state `2 -> 0` change in a **separate extracted scene copy**, reversible; the original APK remains unchanged. This cannot itself install an executable replacement. **If listener is disabled without a running membership callback and original Login continuation, login simply stops.**

## Next GO/NO-GO gate

Need an actual supported code-loading and Unity-owner-thread connection in the compiled CGM app. The new Java class must be packaged and invoked (no existing DEX entry), and the Unity event needs a verified replacement callback that preserves `Login()` exactly once after ACTIVE. Java JNI does **not** automatically intercept Unity's IL2CPP serialized event. The source-adapter `CgmEmailLoginGate.cs` still cannot be injected using exported C# stubs without a matching buildable Unity project or a separately validated ARM64 guest/native integration on a disposable Android runtime.

CGM membership queries must be email-only, TLS-validated, fresh, with failed/expired checks not authorizing original submission. No password or protected packet is sent to CGM membership endpoint by this bridge. Unchanged upstream ANLGarden VIP and official PlayPark/Game/Garden session responses still control their own authorization.

**Artifact:** `CGM_Phase40AH_JNI_Bridge_Checkpoint.zip`, includes all source files, host Java tests, original Phase40AG C source, ARM64 candidate ELF, target audit JSON, reproductions and README. No emulator, live credentials, production server, or original APK changes made.

## Practical limitation

The Android SDK/D8, NDK, matching Unity source project and a proven on-device ARM64 hooking/extension environment are not available in the current workspace. A host JVM pass and ARM64 ELF build is useful JNI interop evidence, but **not a working CGM Auth-First APK or Garden access**.
