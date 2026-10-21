# Phase 40AP — Verified original Unity JavaPluginPreloader route

**Date:** 2026-10-22. **Status:** Static original-client DEX/Manifest chain verified; benign loader-class source created and JVM class-initializer test passed. **No staged class has been placed in the original APK or loaded inside the actual game process.**

## New actual APK evidence

Analyzed archived CGM APK SHA256 `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`, using its actual AndroidManifest and classes.dex. Phase40AM user previously verified Nox62032 installed APK has exact archived Unity scene and ARM64 IL2CPP; Phase40AN found one graphics method instruction change and no flagged loader changes in DEX.

1. Launcher Android manifest directly names `com.unity3d.player.UnityPlayerActivity` with MAIN/LAUNCHER.
2. DEX contains `Lcom/unity3d/player/UnityPlayer;->preloadJavaPlugins()V`.
3. Native DEX instruction inspection: `UnityPlayer.<init>(Context,IUnityPlayerLifecycleEvents)` contains `invoke-static` for method ID `0x219f` (`preloadJavaPlugins()`), at instruction index 182.
4. `preloadJavaPlugins()` has a literal `com.unity3d.JavaPluginPreloader` and invokes `java.lang.Class.forName(String)` (method ID `0x2360`).
5. The original DEX does **not** declare `Lcom/unity3d/JavaPluginPreloader;`.
6. This implies a candidate startup **class-loading** path: supplying that exact class descriptor could cause Java class initialization. It does **not** prove Unity app-process loading on device, or that a compiled JNI native gate is authorized to run and can safely intercept the original Unity CONFIRM callback.

## New conversation artifact

`CGM_Phase40AP_Unity_Preloader_Proof.zip` SHA256 `1aa78425a46a0e821289bd22809167d3ab968abbdda44772d5b0cc1010995fe3`, 7 files. Includes:

- `src/com/unity3d/JavaPluginPreloader.java` containing only `System.loadLibrary("cgm_authfirst_gate")` in a caught/logged static initializer. It makes no network, account, login, Unity callback or VIP/session changes.
- `scripts/verify_preloader_route.py`: read-only hash-constrained, original DEX call-chain/manifest verification using `dex_readonly.py` copied from Phase40AN parser.
- `scripts/build_phase40ap_readonly.ps1`: user Windows **single-command** compiles the benign Java class with existing Android SDK/JDK/D8, copies existing Phase40AK native `.so`, verifies DEX/ELF outputs. Creates only files in its own `staging-only` directory; no ADB, installation, APK repacking, signing or network requests.
- `scripts/verify_stage_parts.py` verifies the staged DEX defines only the expected class, includes `<clinit>` and `System.loadLibrary`, and JNI file is ELF64/AArch64.
- README and offline static route report.

## Tests actually run here

- Original archived APK hash-pinned DEX manifest route test: **PASS**.
- Host JVM `Class.forName("com.unity3d.JavaPluginPreloader")` executes static initializer; absent test host JNI library emits caught `UnsatisfiedLinkError` and host test finishes: **PASS**.
- Python code AST parse: PASS.
- ZIP CRC integrity: PASS.
- Windows SDK D8 build **NOT RUN**; CGM native library loaded within original Unity process **NOT PROVEN**.

User command (after extracting to `D:\CGMTEST\CGM_Phase40AP_Unity_Preloader_Proof`):

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AP_Unity_Preloader_Proof\scripts\build_phase40ap_readonly.ps1'
```

Preserve prior `D:\CGMTEST\CGM_Phase40AM_Installed_Client_Parity\collected\Nox62032_CGM_base_readonly.apk` and `D:\CGMTEST\CGM_Phase40AK_HTTPS_Preflight_Android\app\src\main\jniLibs\arm64-v8a\libcgm_authfirst_gate.so`. No emulator interaction is needed.

## Next GO/NO-GO

After staged DEX+ELF checks PASS, separately design a reversible, explicitly approved testing plan to package both into a **staging CGM APK**, with an independent signature compatibility check before installation. No overwriting/uninstalling the current CGM installation on disposable Nox62032 without an explicit safe restoration plan. Never touch protected Nox62030 or Nox62031.

Even a successful app-process load does not suppress existing Unity serialized Login() Path4300 -> Path4610. Remaining blocker is Unity owner-thread gate interception/resumption, lifecycle and retry semantics and independent ANLGarden/PlayPark/Game/Garden authorization. No bypass of third-party VIP or fabricated login/session success.
