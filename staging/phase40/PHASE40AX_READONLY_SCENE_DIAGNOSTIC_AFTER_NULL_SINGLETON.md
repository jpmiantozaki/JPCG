# Phase40AX — No-install disposable Unity scene diagnostic after Phase40AW NULL singleton

**Date:** 2026-10-22. **Status:** Script authored and static safeguards PASS, ZIP CRC PASS. **No user/device runtime run yet.**

## Evidence and scope
- Phase40AU/AV user-confirmed on-device IL2CPP `LoginServerManager.Login()` metadata method discovery at 12/24 seconds (status100), no invocation.
- Phase40AW user-confirmed disposable Nox62033 signed test APK SHA256 `9033a6bcfae4cf39b8a1d1c0368fb3bc6cf0d8235aa8846deceeca207691e39e` built, installed and launched. Static field `<Instance>k__BackingField` read **NULL** at 12s and 24s (`STATUS=105`). No game login performed.
- Independent static disassembly of the actual archived ARM64 `libil2cpp.so` SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8` verifies LoginServerManager `get_Instance` RVA 0x6A5850, `set_Instance` RVA 0x6A5898, `Awake` RVA 0x6A58E4. Awake contains conditional reference assignment at RVAs 0x6A5994–0x6A599C. **This does not prove Awake ran**, so null at the snapshot could reflect lifecycle/scene/UI state or other causes.

## New Phase40AX diagnostic package

Conversation ZIP `CGM_Phase40AX_NoInstall_Scene_Diagnostics.zip`; SHA256 `d0a19cf9d0268fee37e5fb1fe2af4295e958947a8e4ec58f82115c1a2927c6c1`. Contains one PowerShell script and README. All calls explicitly pin only Nox62033. No other serial/port, no installation/updating/uninstalling/launching, no user input, no credential/text field access, no requests to CGM or third-party endpoints. **The already-running game may autonomously use the network** if it was not externally network isolated.

The one-command script reads: ADB state; API28/arm64; exact CGM package present; focused `dumpsys activity`/window, filtered Unity/Phase40AW/AndroidRuntime logcat, process PID, and screen snapshot via `adb exec-out screencap -p`. All local outputs go to a new `results` directory (will refuse to overwrite it). If screenshot unsupported user should send manual emulator screenshot. User should ensure no sensitive account data appears on screen.

### User Windows command
Extract to `D:\CGMTEST\CGM_Phase40AX_NoInstall_Scene_Diagnostics`. Leave existing disposable Nox62033 as-is, do not click yellow LOGIN:
```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AX_NoInstall_Scene_Diagnostics\scripts\run_phase40ax_readonly.ps1'
```
Send `results\Phase40AX_Current_Emulator_Screen.png` and `results\Phase40AX_Scene_Context_Report.json`. If errors, send console and any `results\unity_log_stdout.txt`. Screenshots/logs are contextual indicators and do not themselves prove Awake executed or explain exact singleton status.

## Status and decision
- Static script safety checks PASS.
- ZIP CRC integrity PASS.
- **No Windows PS execution, no screenshot retrieval, no managed component instance or Login() execution tested by assistant.**
- Do not modify original PathID4300 -> Login() or upstream authorization/session handling.
- Retain protected Nox62030/62031/62032; only disposable Nox62033 for optional observation.

## User initial Phase40AX run: STOP at package-filter guard (2026-10-22)

User ran `run_phase40ax_readonly.ps1` against disposable Nox62033; it stopped at line34 `Expected CGM disposable test package not found; no other app touched.` The original script checked only `adb shell pm list packages com.DeathToB.ANLGarden` against one strict line regex, without printing raw package-manager output. **Do not infer that the app was actually uninstalled**: immediately preceding Phase40AW log reports a verified successful signed test APK install/Unity startup; but the target instance or package state might since have changed. This initial Phase40AX invocation did not capture a screen or change the app.

Corrected read-only kit `CGM_Phase40AX_NoInstall_Scene_Diagnostics_Fix.zip`, SHA256 `6b22727bb48f9d6b6afa4d7fc23fad6c4b4c71997a6a8c5b380737fda66f5c24`, created with (a) exact `pm path` and both filtered+unfiltered `pm list packages` checks, (b) raw ADB stdout/stderr evidence saved on both success/failure, (c) binary-preserving ADB screenshot via .NET output BaseStream, (d) report includes path/list discovery booleans. Static safety regression and ZIP CRC PASS. **Windows/Nox test remains pending**. It does not install/reinstall/edit/launch/click anything or target protected instances.

After extract in `D:\CGMTEST`, run:
```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AX_NoInstall_Scene_Diagnostics_Fix\scripts\run_phase40ax_readonly.ps1'
```

If package genuinely absent or otherwise unconfirmed, STOP and inspect `results/package_path_stdout.txt`, `results/package_listing_all_stdout.txt`. Don't auto-reinstall or infer a Unity scene state. If found, retrieve current screenshot and JSON. No login, original CONFIRM alteration or third-party session authorization test.

## Follow-up Phase40AX v2 user result — CGM package genuinely absent / accidental uninstall suspected

User ran the corrected v2 Phase40AX no-install diagnostic on Nox62033. Both package discovery routes failed to confirm `com.DeathToB.ANLGarden`; `pm path` exited 1. Script stopped before screenshot or game access. User said: "I think the reason is I accidentally uninstalled the last APK." This is consistent with the evidence, but actual full package-query outputs were not uploaded.

Important: The last **previously proven Phase40AW** test-signed original CGM APK remains on the user's Windows host at `D:\CGMTEST\CGM_Phase40AW_ReadOnly_Login_Instance_Probe\build\Phase40AW_SIGNED_TEST_ONLY.apk`, SHA256 `9033a6bcfae4cf39b8a1d1c0368fb3bc6cf0d8235aa8846deceeca207691e39e`. Original test certificate `6fef57b71ce2f70af9cca1df6578620462ec55065a9151f78d0194386ebf25ed`. Uninstalling from disposable Nox62033 does NOT erase prior runtime proof (Phase40AU/AV metadata status 100, Phase40AW singleton null status 105), although any disposable app data lost during uninstall is not recoverable from a fresh install unless backed up.

### Phase40AY guarded recovery package (prepared, not run)

Conversation artifact: `CGM_Phase40AY_Recover_AW_After_Uninstall.zip` SHA256 `9e2269e2772ac24d6a4f5a1d32516ebf13e82fc4f5803f9d3e750d9db9ffe89c`. Pure Windows PowerShell runner plus README. ZIP CRC and static safety tests PASS. **Windows execution not yet tested**.

Extract to `D:\CGMTEST\CGM_Phase40AY_Recover_AW_After_Uninstall`; run one command:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AY_Recover_AW_After_Uninstall\scripts\run_phase40ay_recover_aw.ps1'
```

Script requires exact local Phase40AW signed APK SHA256, connected Nox62033 (Android API28 and arm64), consistent package absent/present evidence from unfiltered `pm list packages` and `pm path`. If absent, installs approved signed AW test APK once on **only** Nox62033; if present, skips installation. Always read-only pulls installed base.apk and requires exact SHA256 before launch; then freshly launches Unity original Activity without LOGIN, waits 30s, collects focused Unity/Phase40AW log, activity state, PNG screenshot and JSON into timestamped results folder. No additional Java/native build, no original Login/CONFIRM modification, no real credentials, no network requests by the script itself. **Normal app startup can independently contact original servers**; network isolate if strict offline test required. No protected Nox62030/31/32 touched.

The screenshot and activity state are **context clues only**, not proof that `LoginServerManager.Awake()` executed or that original runtime AUTH-FIRST injection is complete. If report/screenshot contains user account details, redact them before sharing. Do not proceed to active login tests or server-session manipulation.
