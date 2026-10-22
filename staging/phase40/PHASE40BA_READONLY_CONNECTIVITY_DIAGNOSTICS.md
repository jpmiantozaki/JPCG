# Phase40BA — Read-only Nox62033 network/error diagnostics after failed outer LOGIN

**Prepared:** 2026-10-22. **Status:** Offline ZIP integrity and static target/no-install guard checks PASS. **Not run on user's Windows emulator yet.** Builds on Phase40AZ user evidence, not another new APK.

## User Phase40AZ finding

- The pre-existing disposable CGM test APK is SHA256 `9033a6bcfae4cf39b8a1d1c0368fb3bc6cf0d8235aa8846deceeca207691e39e` (Phase40AW).
- User manually pressed original *outer yellow LOGIN* once with no username/password or inner CONFIRM.
- Actual screenshot displays **Web Error. Cannot connect to destination host** on initial Audition Next Gen landing page; never reached credential screen.
- Runtime 12-second and 24-second native snapshots remained singleton null (`STATUS=105`), despite original button being bound to the LoginServerManager component.
- Verified original compiled ARM64 `LoginServerManager.ConnectToServer(bool)` at RVA `0x6A8488` calls `FetchServerData()` at `0x6A84D0`, then starts its Unity coroutine; iterator `MoveNext` at `0x6A9864` uses Unity request/error pathway. This means the yellow button's first-stage connection/configuration failed somewhere. **Host and failure type UNKNOWN**; do not claim PlayPark outage or authorized VIP rejection based solely on error string.
- Static field `LoginServerManager.Instance` being null does **not** alone establish no live component, because serialized button targets component PathID4610 directly; original listener is untouched.

## New one-command diagnostic

Conversation artifact: `CGM_Phase40BA_NoInstall_Connection_Diagnostics.zip`, SHA256 `f3f6bc1ac0ed7629783c5628ddcb622ddb9076e10def4fa8f7fc74ec79d4280d`.

PowerShell `scripts/run_phase40ba_readonly.ps1`: targets only ADB `127.0.0.1:62033` and checks API28 and package presence, gathers existing logcat warning/error/info for Unity and Phase40AW plus AndroidRuntime/chromium; collects local `dumpsys connectivity`, active window markers, net.dns/proxy/private DNS settings. Creates timestamped JSON report and redacted-by-selection relevant log lines. **No install, reinstall, uninstall, launch, stop, click, input, network probe, HTTP/DNS lookup or credentials.** The game itself may already be online; app remains unchanged. Logs may have rotated. Raw `dumpsys connectivity` can contain network identifiers; review before sharing. No protected Nox ports.

Extract under `D:\CGMTEST\CGM_Phase40BA_NoInstall_Connection_Diagnostics` and run exactly:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40BA_NoInstall_Connection_Diagnostics\scripts\run_phase40ba_readonly.ps1'
```

Output under `results\Phase40BA_<timestamp>`: `Phase40BA_Connection_Diagnostic_Report.json`, `Phase40BA_Relevant_Log_Lines.txt`, `Phase40BA_Network_Summary_Lines.txt`. Ask user to upload JSON and relevant logs (after privacy review). If original request host cannot be found, next step is offline static source/IL2CPP request URL identification, not rewriting or ignoring server failure.

## Offline checks here

ZIP CRC PASS; hard-coded target only Nox62033; no ADB install/push/uninstall/other instance, no scripted button click or launch; no direct external host contacts. **No Windows PowerShell or ADB executed in this workspace.**

## Long-term safety

Preserve original PlayPark login authentication and independent ANLGarden VIP/Game/Garden session decisions. Do not disable original inner CONFIRM listener, fabricate session, bypass connection failure, or treat missing singleton as verified destroyed component.

## Phase40BA user Windows result — diagnostics captured, no host-specific failure logged (2026-10-22)

User ran `run_phase40ba_readonly.ps1` on Nox62033 and pasted JSON + filtered log text:
- `status=READONLY_DIAGNOSTICS_CAPTURED`, API28, expected `com.DeathToB.ANLGarden` installed, focus `UnityPlayerActivity`.
- Android `WIFI CONNECTED/CONNECTED` default net100 `VALIDATED`, `lastValidated=true`; DNS addresses include `192.168.1.1`, `192.168.68.1`, `114.114.114.114`, `8.8.8.8`. `http_proxy=null`. `private_dns_mode=null`. `network_dns_primary` empty but DNS addresses in LinkProperties are not empty.
- Filtered app logs contain **only** prior native `PHASE40AW_PROBE_12SEC/24SEC STATUS=105` from user sessions; no UnityWebRequest exception/HTTP status/URL appeared. Therefore cannot blame DNS/TLS/public server outage from this evidence.
- NO new external requests were made by 40BA.

**Critical prior-project evidence rediscovered in user-owned Phase26N backup:** Phase2.6K actually tested same originally archived CGM build's first-stage yellow LOGIN with `GET http://127.0.0.1:8091/authenticate.php?version=1026`, using *emulator-specific ADB reverse tcp:8091 tcp:8091* to a local CGM-owned PHP upstream. The intentionally synthetic stage0 returned HTTP503; subsequent Phase2.6L used reverse tcp:8091 -> Windows tcp:8094 and synthetic MGServerResponse, not a legitimate PlayPark session. This *does not* prove current Nox62033 port mapping exists. Original IL2CPP metadata URL literal #7157 is `http://127.0.0.1:8091/authenticate.php?version=`; #7156 analytics, #7158 redeem, #7159 session, #7160 verify also use localhost 8091. Same original libil2cpp and metadata persist in Nox62033 staged APK (Phase40AW content parity).

**Most useful next test: Phase40BB passive emulator/host port state preflight**. Read only `nox_adb.exe -s 127.0.0.1:62033 reverse --list` and Windows host `Get-NetTCPConnection -State Listen -LocalPort 8091/8094/18992`, without starting PHP, adding reverse mapping, or tapping original LOGIN. If missing ADB reverse mapping or Windows listener, the local prerequisite is absent and explains *a likely* cause of Web Error; do not imply other public PlayPark servers are down. Do not use synthetic endpoints for real credentials; if needed later, a local operator-owned, fail-closed test can be independently approved, without forging upstream auth/session data. No protected Nox62030/31/32 operations.
