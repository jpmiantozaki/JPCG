# Phase 40BE — Manual READY/CLICKED gates for original yellow LOGIN route proof

**Date:** 2026-10-23. **Status:** Corrected user-controlled test script and 503-only local responder offline tests PASS; Windows/Nox runtime not yet executed.

## Why 40BC was inconclusive

User reported the original Unity game takes at least **one minute** to finish loading, and the previous Phase40BC PowerShell script printed "click yellow LOGIN" immediately after `am start -S` and then unconditionally executed `Start-Sleep -Seconds 35`. The script may therefore have ended its local 503 server and ADB reverse mapping *before the user could click the button*. Observed `NO_LOCAL_HTTP_REQUEST_OBSERVED` with 0 routes in Phase40BC is **not proof** that the original APK does not use `/authenticate.php` and should be treated as inconclusive for timing reasons until retested. Other causes remain plausible.

## Corrected test

Conversation artifact: `CGM_Phase40BE_Manual_Ready_503_Proof.zip`, SHA256 `e69032d3b2680d04edbe6fe88d469847f28dcdff3afdad522f48f4d83cd3a025` (4 files, ZIP integrity PASS). Reuses original 40BC `local_failclosed_8091.py` unchanged; response HTTP 503 to every route, logs only route type and method, never query/password/session. Revised PowerShell `scripts/run_phase40be_manual_ready.ps1` removes the fixed 35-sec window:

1. Exact installed Phase40AW APK SHA256 check by read-only `adb pull` on disposable `127.0.0.1:62033`.
2. Check 8091 mapping and listener absent, start own Windows 127.0.0.1:8091 503-only responder and create temporary **only 62033** ADB reverse mapping.
3. Require explicit `START`; launch original Unity Activity. **Wait at `Read-Host READY` indefinitely** until the human observes the fully loaded yellow LOGIN screen (60–120+ seconds permitted). Do not auto-click.
4. After `READY`, user clicks outer yellow LOGIN **once** and observes its response, then **wait at `Read-Host CLICKED` indefinitely**; script cannot finish its HTTP capture while user is still loading or clicking.
5. On `CLICKED` collect sanitized local HTTP route counts and timestamps. Any other input cancels. `finally` removes only mapping and stops server. No APK build/install/edit, no user credentials/CONFIRM, no fabricated auth/config/session/VIP/Garden response, no protected Nox62030/31/32.

**Limit:** The existing Phase40AW native singleton 12/24-sec snapshots are scheduled internally *relative to app start* and **are not moved** by these PowerShell prompts. Phase40BE tests HTTP routing only, not post-click singleton lifecycle. Phase40BD's independent no-game transport test needs no Unity loading wait.

## Source and tests

- Existing 503-only Python server tested offline for `/authenticate.php` and `/session.php`: both 503, sanitized route-only log excluding secret query values.
- PowerShell static safety assertions: only Nox62033, original APK hash guard, user READY/CLICKED gates, no `Start-Sleep 35`, no simulated touches/install, cleanup in `finally`: PASS.
- ZIP CRC PASS. No on-device run here.

### One-command user execution

Extract ZIP into `D:\CGMTEST\CGM_Phase40BE_Manual_Ready_503_Proof` and run:
```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40BE_Manual_Ready_503_Proof\scripts\run_phase40be_manual_ready.ps1'
```
Then follow `START`, wait for yellow screen and type `READY`, click outer yellow once, then `CLICKED` after observing response. Report `results\Phase40BE_<timestamp>\Phase40BE_Result.json` and sanitized local route log. Runtime may independently contact pre-existing upstream server, not suitable for strictly zero-external-traffic test.

## Actual Phase40BE result: PASS on disposable Nox62033 (2026-10-23)

User ran `scripts/run_phase40be_manual_ready.ps1` with START → waited for full Unity launch → READY → manually clicked only outer yellow LOGIN once → CLICKED after observing the response. Same Phase40AW APK SHA256 `9033a6bcfae4cf39b8a1d1c0368fb3bc6cf0d8235aa8846deceeca207691e39e` verified on Nox62033.

Actual console:
```text
[40BE] RESULT: EXPECTED_LOCAL_AUTHENTICATE_HIT_503 ; /authenticate.php hits=1 ; total requests=1
[40BE] CLEANUP: remove ONLY tcp:8091 reverse mapping from Nox62033.
[40BE] CLEANUP: stop disposable local 503 responder.
[40BE] Cleanup attempted. No game APK modified; protected emulator instances untouched.
```
User screenshot displays original Unity yellow LOGIN background with alert `Web Error. HTTP/1.1 503 Service Unavailable` (distinct from Phase40AZ/BC `Cannot connect to destination host`).

**Conclusions:**
1. Original compiled CGM first-stage outer LOGIN uses the mapped `/authenticate.php` path with a functioning Nox62033->host port8091 ADB reverse, and the app surfaces the fail-closed HTTP503 error as expected.
2. Phase40BC `0 requests` is **not** proof of failed routing: user said game needed ≥1 minute to load, previous script waited only 35 seconds. Manual READY/CLICKED timing resolves that test deficiency.
3. The temp host does NOT return successful configuration, a login/game packet or Garden entitlement. No username/password, inner CONFIRM, CGM ACTIVE or official PlayPark/ANLGarden session occurred.
4. This does **not** prove the 12/24-second native singleton snapshot will change; those probes are timed from app startup, typically before manual click.
5. Next useful non-bypass diagnostic should *not* repeat 503 route confirmation. Instead inspect known Phase26L local synthetic configuration fixture and its constraints, then—if warranted and authorized—test a strictly local, synthetic, fail-closed configuration transition to credential UI, without entering credentials or contacting real upstream game auth or claiming a valid session. Keep rollback and no PlayPark/Garden bypass requirements.
