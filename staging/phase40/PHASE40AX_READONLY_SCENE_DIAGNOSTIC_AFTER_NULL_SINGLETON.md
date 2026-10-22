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
