# Phase 40AU — Original Unity IL2CPP managed Login() metadata runtime probe

**Date:** 2026-10-22. **Status:** Original app-process loader PREVIOUSLY PASSED on disposable Nox62033 (Phase40AT). Phase40AU read-only metadata-probe source and guarded one-command Windows build/install harness prepared, source/host regression checks PASS. **Phase40AU device runtime not executed.**

## Proven Phase40AT user results

Windows Phase40AT verified the signed original CGM test APK hash `446befe5692a552d17e8d1a6bf799e838112be62e50abac101de8b08eab18ee1` installed on disposable Nox62033, launched `com.DeathToB.ANLGarden/com.unity3d.player.UnityPlayerActivity`, and reported `LOADER_ONLY_RUNTIME_PASS`. User's logcat contained:
```
10-22 19:23:51.321 I/CGMPhase40AP( 4135): PHASE40AP_NATIVE_LIBRARY_LOADED (loader only; no Unity binding)
10-22 19:29:15.837 I/CGMPhase40AP( 4380): PHASE40AP_NATIVE_LIBRARY_LOADED (loader only; no Unity binding)
```
Native ELF successfully loads within the staged original compiled CGM Unity process. **No native IL2CPP method lookup / login gate yet.**

## New Phase40AU artifact

Conversation ZIP `CGM_Phase40AU_Original_Unity_Method_Probe.zip` SHA256 `ae7cdff080dc797ef494d950cb323cda5acd9b7ab89e9f4ae855fa7df3604c03`, 13 files, CRC verified.

- Preserves original Phase40AH policy and JNI C source bytes from 40AK. Adds `native/phase40au_readonly_il2cpp.c` and `com/unity3d/JavaPluginPreloader.java` with two scheduled 12/24-second JNI probes after Unity startup.
- Native C uses `dlopen("libil2cpp.so", RTLD_NOW | RTLD_NOLOAD)`, `dlsym` native metadata APIs, `il2cpp_domain_get`, thread attach only if not attached, `il2cpp_domain_assembly_open("Assembly-CSharp")` with `.dll` fallback, `il2cpp_class_from_name("", "LoginServerManager")`, `il2cpp_class_get_method_from_name("Login", 0)`, then detaches if newly attached. **Never calls il2cpp_runtime_invoke or Login/SendLogin; never touches UI state or credentials**.
- `scripts/run_phase40au_full.ps1`: default one-command guarded build, sign **with the same local Phase40AR test-only keystore**, content-compare all 167 unchanged original APK entries, verify prior installed Nox62033 copy by ADB pull/hash BEFORE updating, update ONLY `127.0.0.1:62033`, verify newly installed APK SHA256, launch original Unity Activity WITHOUT clicking LOGIN, capture only `CGMPhase40AU` and `AndroidRuntime` logcat. Two delayed probes; output statuses including `READONLY_MANAGED_METHOD_DISCOVERY_PASS` on real runtime marker.
- `scripts/rollback_phase40au_disposable_only.ps1` is **optional**, refuses to act unless currently installed APK on 62033 exactly equals new Phase40AU staged APK; then reinstalls exact previously proven Phase40AR test APK (same test signing certificate) and verifies restored hash. Rollback cannot target other ports.
- Manual snapshot always advisable. Original game has normal startup networking possibility; externally isolate disposable if strictly offline required. No membership API request, credentials, login, session, VIP, Garden, or original scene edits in injected probe.

## Offline checks PASS

- C source compiles `clang -fsyntax-only -std=c11 -Wall -Wextra -Werror` against JNI headers and test Android log stub. **Not Android NDK build.**
- Revised Java preloader compiles with host Java 8 target and Android Handler/Looper/Log API stubs. **Not Android D8 build.**
- Guard/safety static tests PASS.
- 167-entry signed content verifier synthetic fixture PASS and intentional payload tamper properly rejected.
- ZIP integrity CRC PASS.
- Windows SDK/NDK build, signed APK parity on real Windows, and Nox62033 runtime Login() metadata discovery **NOT TESTED YET**.

## User command

After extracting under `D:\CGMTEST\CGM_Phase40AU_Original_Unity_Method_Probe`, with exact Phase40AR test APK still installed on disposable Nox62033:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AU_Original_Unity_Method_Probe\scripts\run_phase40au_full.ps1'
```

On success logs should include `PHASE40AU_LOGIN_METHOD_DISCOVERED (pointer not logged, never invoked)` and final `PHASE 40AU ORIGINAL LOGIN() METADATA RESOLVED, NOT INVOKED`. If build fails or native finds no method, send output and `results\Phase40AU_Unity_Method_Probe_Log.txt`, don't blindly reinstall/overwrite. No user account input.

Optional explicit rollback after capturing logs:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AU_Original_Unity_Method_Probe\scripts\rollback_phase40au_disposable_only.ps1'
```

## What it would and would not prove

A PASS would prove the already-loaded JNI library can locate original managed Login() method metadata within the actual compiled Unity process (in a disposable test-signed APK). **It would not** prove an instance of LoginServerManager, safe Unity-owner-thread callback, bypass-free pre-submit suppression, member ACTIVE authorizing original login, unchanged official PlayPark upstream auth or independent ANLGarden VIP/Garden session acceptance. Original CONFIRM persistent binding PathID4300->Login() component PathID4610 is never touched by Phase40AU. Avoid unsupported patching/hooking or upstream VIP/session bypass.

## Windows Phase40AU build PASS; installer marker detection STOP (2026-10-22)

User ran `scripts/run_phase40au_full.ps1`. Full NDK/D8 rebuild, signed package identity, same Phase40AR test certificate `6fef57b71ce2f70af9cca1df6578620462ec55065a9151f78d0194386ebf25ed`, preservation of all 167 original APK ZIP payloads and overlay byte hashes PASS. Windows output verified previous installed Nox62033 app is exact Phase40AR signed APK SHA256 `446befe5692a552d17e8d1a6bf799e838112be62e50abac101de8b08eab18ee1`. Newly built Phase40AU signed test APK SHA256 `cc80b36dda5adb9eca64633f1c70c16a6c7c15b4f5506a49f37411d5fe76f39a`.

The script then called `adb install -r` but STOPPED at `ADB installer did not say Success:` with blank captured stdout. The `Adb` helper uses `Start-Process` with separate stdout/stderr and asserts exit code 0, but **returns stdout only**; some Nox ADB versions put `Success` on stderr. Thus, successful ADB exit is implied by reaching that check, but actual installed bytes are **not yet verified** and Unity launch / IL2CPP read-only method discovery **did not run as part of this script**. No conclusion yet on native metadata lookup.

### Phase40AV guarded completion

New conversation kit `CGM_Phase40AV_Guarded_Installed_AU_Recovery.zip` SHA256 `e519e4609b8ca63c9d574a2d66fbfb7514f148068d4744a1cc52328437480a66`. Python static guard regression PASS and ZIP integrity PASS. Windows runtime not yet run.

After extracting to `D:\CGMTEST\CGM_Phase40AV_Guarded_Installed_AU_Recovery`, one command:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AV_Guarded_Installed_AU_Recovery\scripts\run_phase40av_complete.ps1'
```

This read-only pulls Nox62033 installed `com.DeathToB.ANLGarden` base.apk first and accepts **only** Phase40AU exact SHA (skip install) or Phase40AR exact SHA (perform one guarded update using Phase40AU previously built APK); any other SHA or absent app stops. Installed Phase40AU exact SHA required before launch. Uses process-exit-check + both ADB output streams. Clears disposable logcat, uses `am start -S` to fresh-launch Unity without clicking/login, waits for the 12/24 second probes, captures `CGMPhase40AU`/AndroidRuntime logs, emits JSON. Never connects to protected Nox62030/31/32 or accesses credentials, membership endpoint, official PlayPark/Garden login/session flows. Original app normal startup might independently contact servers; externally isolate disposable emulator when offline assurance is needed.

Even a Phase40AU method-discovery PASS proves only `LoginServerManager.Login()` managed metadata resolution, **not invocation, owner-thread instance lookup, persistent CONFIRM suppression, fresh membership ACTIVE, or PlayPark/ANLGarden VIP/Garden acceptance**.

## User-confirmed Phase40AV runtime PASS — 2026-10-22

User ran `run_phase40av_complete.ps1` on Nox62033. Before-install hash verified `CC80B36DDA5ADB9ECA64633F1C70C16A6C7C15B4F5506A49F37411D5FE76F39A`: Phase40AU **already installed**, so installer skipped. Fresh-launched `com.DeathToB.ANLGarden/com.unity3d.player.UnityPlayerActivity`, with no login or credentials.

User supplied device logs:
```text
10-22 19:53:29.724 I/CGMPhase40AU(5223): PHASE40AU_NATIVE_LIBRARY_LOADED
10-22 19:53:41.736 I/CGMPhase40AU(5223): PHASE40AU_LOGIN_METHOD_DISCOVERED (pointer not logged, never invoked)
10-22 19:53:41.736 I/CGMPhase40AU(5223): PHASE40AU_PROBE_12SEC STATUS=100
10-22 19:53:53.729 I/CGMPhase40AU(5223): PHASE40AU_LOGIN_METHOD_DISCOVERED (pointer not logged, never invoked)
10-22 19:53:53.729 I/CGMPhase40AU(5223): PHASE40AU_PROBE_24SEC STATUS=100
[40AV] RESULT: READONLY_MANAGED_METHOD_DISCOVERY_PASS
```

**Proven:** the added JNI module can resolve original `Assembly-CSharp/LoginServerManager/Login()` metadata through exported IL2CPP APIs during *real* modified CGM Unity process execution. Method was **never invoked**; no Unity owner-thread hook, login instance, membership ACTIVE, upstream authorization or Garden entry proved. This is an actual on-device read-only metadata result, not a host-only fixture.

Next: Phase40AW verifies *only* whether the original LoginServerManager static `<Instance>k__BackingField` holds a non-null managed reference, without dereferencing, invoking, or reading credentials.
