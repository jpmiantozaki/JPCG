# Phase 40AS — New disposable emulator original Unity process loader-only proof

**Status (2026-10-22):** Created source-only test orchestration kit and passed local static/safety tests; **Windows PowerShell execution and Nox runtime not yet tested**. Builds on user-confirmed Phase40AR signed test APK and distinct signing key, not a new isolated test app.

## Prior confirmed evidence
- Archived scene original inner CONFIRM PathID4300 persistent original `Login()` PathID4610 remains unchanged. Phase40AM installed Nox62032 scene/native equivalence PASS.
- User Phase40AR Windows **sign-and-verify PASS**; same original package ID `com.DeathToB.ANLGarden` version 1026 but test-only SHA256 `446befe5692a552d17e8d1a6bf799e838112be62e50abac101de8b08eab18ee1`; signing certificate `6fef57b71ce2f70af9cca1df6578620462ec55065a9151f78d0194386ebf25ed`; original package certificate `9f9f13f2ebeda8ada9665daa9499cd3859cae64a1a87de42efa62735c3ab3a23` (different, not upgrade compatible).
- Phase40AP archived/staged DEX identified `UnityPlayer.preloadJavaPlugins()`, `Class.forName("com.unity3d.JavaPluginPreloader")` and staged class initializer with `System.loadLibrary("cgm_authfirst_gate")`, log tags `CGMPhase40AP` loaded/failed.

## New phase test package
`CGM_Phase40AS_Disposable_Runtime_Preloader.zip` SHA256 `d13cbbfbe6acc84685342a63d11a82e282e3dfb73e8f642199e716d2e8e77dee`.

Only contains PowerShell orchestrator + README + Python static guard test. Does NOT include protected APK binary or signing private keystore. Need existing user Phase40AR signed APK file.

One-command `scripts/run_phase40as_disposable_only.ps1`:
- targets ONLY exact ADB TCP serial `127.0.0.1:62033` (a **new blank Android 9** Nox Multi-Drive instance which the user must create, **not clone**);
- verifies exact signed APK SHA256, test signing cert, Android package ID, API28, ARM64 and that `com.DeathToB.ANLGarden` is NOT already installed;
- then installs the signed test app **once** on new disposable 62033 and launches `com.DeathToB.ANLGarden/com.unity3d.player.UnityPlayerActivity`;
- after 12 seconds collects filtered `CGMPhase40AP` and `AndroidRuntime` logcat to JSON/text, prints PASS only if `PHASE40AP_NATIVE_LIBRARY_LOADED` is observed, else returns an explicit failure/unknown marker status;
- **does not** click CONFIRM, enter credentials, issue membership requests, or bypass VIP/login/session responses. Does not contact/alter 62030, 62031, 62032.
- **NOT guaranteed offline**: unmodified game startup may independently contact its normal external endpoints; to avoid that, isolate new emulator networking externally first.

The script refuses to overwrite an existing results report or installed CGM package on 62033, never uninstalls/purges original game data, doesn't touch existing snapshots, and preserves test reports for user inspection. User may delete ONLY the new disposable instance after collecting results.

## Tests actually performed

Python static checks of exact target, protected-port exclusion, package-exists guard, expected hash/cert and marker reporting: PASS. ZIP CRC: PASS. **No actual ADB installation, emulator creation, Android process class loading, network traffic, or Unity callback integration performed by assistant**.

## User instructions

In Nox Multi-Drive, create a brand-new blank Android9 Nox instance with ADB `127.0.0.1:62033`; if port differs **stop** rather than target any protected instance. Extract the kit into `D:\CGMTEST\CGM_Phase40AS_Disposable_Runtime_Preloader`.

Run one command:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AS_Disposable_Runtime_Preloader\scripts\run_phase40as_disposable_only.ps1'
```

Reports written to `...\results\Phase40AS_Runtime_Result.json` and `...\results\Phase40AS_Preloader_Log.txt`.

**Evidence interpretation:** Even a log marker PASS proves only Java class initialization and successful JNI library load in the original compiled Unity app startup process. It does **not** establish a verified owner-thread gate, suppression of existing persistent `Login()`, CGM ACTIVE path, or legitimate official PlayPark and ANLGarden Garden/session acceptance. Never modify or disable original CONFIRM listener without a validated continuation and rollback path.

## Phase40AS first user run — BEFORE INSTALLATION: empty-app guard false-positive

User created/connected a fresh target `127.0.0.1:62033` and independently verified `adb shell pm path com.DeathToB.ANLGarden` produces no matching `package:` path. The original Phase40AS v1 script verified APK hash `446BEFE5692A552D17E8D1A6BF799E838112BE62E50ABAC101DE8B08EAB18EE1` and test-signing certificate `6fef57b71ce2f70af9cca1df6578620462ec55065a9151f78d0194386ebf25ed` but STOPPED at `Could not inspect app existence on Nox62033.` prior to APK install. Root cause: `adb shell pm path <not-installed-package>` can report exit status 1 despite successful negative existence inquiry; v1 incorrectly required exit 0.

### Corrected Phase40AS runner (v2)

New conversation ZIP `CGM_Phase40AS_Disposable_Runtime_Preloader_Fix.zip`, SHA256 `fa585418e70489a9f7af41275ff4da9091820077cd578a7de2e4ad28f7e5e533`. Replacement root directory `D:\CGMTEST\CGM_Phase40AS_Disposable_Runtime_Preloader_Fix`. Retains all existing target safeguards, APK hash/certificate checks, install once, logcat and fail-safe reports. Replaces missing-package check with `adb shell pm list packages com.DeathToB.ANLGarden`: successful exit0 with empty output is legitimate uninstalled state, exact anchored `package:com.DeathToB.ANLGarden` still blocks installation; nonzero package-manager errors stop. Python static safety regression and empty-package case tests PASS; ZIP CRC integrity PASS. **Corrected Windows runtime has NOT YET been run; no install or game modification has occurred.**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AS_Disposable_Runtime_Preloader_Fix\scripts\run_phase40as_disposable_only.ps1'
```

Use the SAME new fresh Nox62033 instance only if it still has no original game app; protected Nox62030/31/32 off limits. No login, credentials, or official game/VIP/Garden sessions. Startup may contact original game's own backend unless test emulator networking externally disabled. Expect `PHASE40AP_NATIVE_LIBRARY_LOADED` log only if preloader/native library really loads inside the staged Unity app. This is not a working membership gate.

## Phase40AS second user run — likely installed, script stopped on stderr 'Success' (2026-10-22)

User reran corrected v2 script on fresh Nox62033. Verified exact signed APK SHA256 `446BEFE5692A552D17E8D1A6BF799E838112BE62E50ABAC101DE8B08EAB18EE1` and test signing certificate, Android API28 with ARM64 support, and confirmed no original CGM installed. The log reached:
```text
[40AS] Install signed staging APK on NEW Nox62033 only
nox_adb.exe : Success
... script run_phase40as_disposable_only.ps1:23 char:16
$lines = @(& $Exe @Params 2>&1)
NativeCommandError
```
Likely install success on emulator, but **not yet independently verified by device pull/hash**. PowerShell 5.1 converted Nox ADB's stdout/stderr success line into an error because `$ErrorActionPreference = 'Stop'`; the script **never reached Unity Activity launch or runtime log capture**. Do NOT repeat Phase40AS installer on now-occupied Nox62033.

### Phase40AT — Resume *installed* app ONLY

New kit `CGM_Phase40AT_Resume_Installed_Preloader.zip` SHA256 `17d51f451e6fe2c3191a294e12959d7e84dea8a23d6fe99605aa8d1a58ccaf2e`. Uses Windows `Start-Process` stdout/stderr redirection + exit-code checks to avoid the PS5.1 NativeCommandError on a successful ADB stderr string. Pins the signed local Phase40AR APK SHA256 and hardcoded disposable `127.0.0.1:62033`, checks Android API28/arm64 and exact package, pulls installed `base.apk` read-only, requires its SHA256 equals exact Phase40AR signed APK, and **then** launches `com.DeathToB.ANLGarden/com.unity3d.player.UnityPlayerActivity`. After 12 sec captures `CGMPhase40AP`/AndroidRuntime log and saves JSON/text in own results folder. **No install, reinstall, uninstall, push, modification of original Login() or protected emulators 62030/31/32.** It stops if app is missing or its installed bytes differ. The original app's ordinary startup may still cause network activity; no credentials or login tests.

One-command Windows test, after extracting ZIP into `D:\CGMTEST`:
```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AT_Resume_Installed_Preloader\scripts\run_phase40at_resume_only.ps1'
```
Output `results\Phase40AT_Runtime_Result.json` and `results\Phase40AT_Preloader_Log.txt`. Static program safety tests PASS, ZIP CRC PASS, **Windows run not yet performed**. Even `LOADER_ONLY_RUNTIME_PASS` only proves Java preloader and native ELF loaded during original compiled CGM startup; CONFIRM callback and membership ACTIVE/PlayPark/Garden authorization remain untested.
