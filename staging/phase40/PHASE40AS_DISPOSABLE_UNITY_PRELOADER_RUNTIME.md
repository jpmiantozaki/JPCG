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
