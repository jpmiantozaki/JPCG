# Phase 40AZ — Original outer yellow LOGIN vs LoginServerManager.Instance lifecycle

**Date:** 2026-10-22. **Status:** User-supplied actual Phase40AY screenshot and JSON/log reviewed; a one-command no-build/no-install manual-button observation test package built, static safety tests and ZIP CRC PASS. **User has not yet run Phase40AZ**.

## Verified reason for this test

The real Phase40AY PNG shows colorful AUDITION NEXT GEN launcher with **one large yellow Login button** and no credential text fields or inner CONFIRM. JSON verifies exact installed Phase40AW signed test APK SHA `9033A6BCFAE4CF39B8A1D1C0368FB3BC6CF0D8235AA8846DECEECA207691E39E` on disposable Nox62033 and native lib loaded. Phase40AY startup log shows same PID3092, `PHASE40AW_PROBE_12SEC STATUS=105` at 21:21:12 and `PHASE40AW_PROBE_24SEC STATUS=105` at 21:21:24. This **proves NULL singleton while the GUI remained at the landing screen**, not a lifetime absence of manager. Prior serialized Unity callback map: outer yellow LOGIN PathID4164 -> `ConnectToServer(false)` on LoginServerManager component PathID4610; inner CONFIRM PathID4300 -> original `Login()` on that component (unchanged).

## New package / safeguards

Conversation ZIP: `CGM_Phase40AZ_Outer_Login_Lifecycle.zip` SHA256 `60af4eef1688fabdd656ef2511603184e3a581b8b58099b3887d8cef7f32ac4f`.

- Contains `scripts/run_phase40az_one_test.ps1`, README and offline static safety test.
- Pins only `127.0.0.1:62033`, Android API28, arm64 and exact previously installed Phase40AW signed APK SHA by read-only pull. Does **not** build/sign/install/reinstall/uninstall/push game, change scene/listeners, or use protected Nox ports.
- Requires user to enter START, restarts only *disposable original Unity test app* via `am start -S` to reset existing 12/24sec read-only singleton snapshots, captures initial PNG, and prompts user to **manually click exactly once the outer yellow LOGIN button** before 12-second probe. No automated tap. Then waits, captures follow-up PNG, native singleton probe logs and JSON. No credential fields are read/submitted, no inner CONFIRM invoked, no Auth-First active gate or third-party session access attempted.
- **Explicit caveat:** pressing outer LOGIN uses the game's original `ConnectToServer(false)` and **may contact original PlayPark login server even with no credential submission**. Do not run if user wants zero external server contact. Independent ordinary app startup traffic might occur. User must not enter password, press inner CONFIRM or redeem codes.
- Static test PASS: serial fixed 62033 only, expected signed APK SHA pin, no install/push/uninstall commands, no scripted tap, read-only log/screens. ZIP CRC integrity PASS. **Not yet run on Windows/Nox**.

## One user command

Extract into `D:\CGMTEST\CGM_Phase40AZ_Outer_Login_Lifecycle` then run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AZ_Outer_Login_Lifecycle\scripts\run_phase40az_one_test.ps1'
```

Type `START` when prompted. Once script prints click prompt, tap visible *initial yellow LOGIN* once while test is running; let script finish. Send the full PowerShell output and new `results\Phase40AZ_<timestamp>\Phase40AZ_Outer_Login_Lifecycle_Report.json`, `Phase40AZ_Outer_Login_Lifecycle_Log.txt`, `Phase40AZ_After_Outer_Login.png`. **Do not upload installed APK copy or any private account info.**

## Decision matrix

- `110` status at 12 and/or 24 seconds: native IL2CPP backing singleton field was non-null at snapshot after manual outer LOGIN. Still no object dereference, safe Unity owner thread, credential gate, callback replacement, official login, or Garden authorization proof.
- `105` at both times: remained NULL even after attempted manual outer LOGIN; screenshot/log distinguishes server-connection failure/scene still splash from actual credential scene. Don't infer manager permanently absent.
- Missing/unexpected snapshots: review logs and startup state; no automatic reinstallation.

Main Phase40AF inner CONFIRM PathID4300 persistent Login() remains ON; never substitute server decisions or bypass PlayPark/ANLGarden VIP and Garden authorization.
