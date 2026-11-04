# Phase 40BS — Live one-shot ordinary PlayPark CONFIRM route after CGM ACTIVE

**Evidence checkpoint:** 2026-10-25 (user's Nox/Windows local time, UTC+08).  
**Observed on:** disposable experimental Nox `127.0.0.1:62033`; installed **Phase 40BP** signed APK.  
**Scope:** isolated localhost `/authenticate.php` and `/session.php` fixture; **NOT official PlayPark authentication**.

## Result: one-shot local submission route verified

The user ran the previously delivered `CGM_Phase40BS_OneShot_PlayPark_Submission_Test.zip` with the existing synthetic configuration PHP on Windows port **8094**, existing no-read TCP login listener on **18992**, and the Phase 40BP CGM membership UI gate. The one-shot helper temporarily routed only `62033` app port `8091` to a privacy-safe local probe on **8095**.

After the user interacted with the original PlayPark credential CONFIRM using a deliberately fictional password, the probe reported exactly **one** `/session.php` endpoint request and restored the prior configuration mapping.

### Exact sanitized terminal result provided by user

```text
Only one local /session.php request is counted and intentionally rejected.
Use CGM ACTIVE email and fictional password ONLY. Never submit a real password.
PHASE40BS_CONFIG_REQUEST_SEEN
PHASE40BS_SESSION_ENDPOINT_REQUEST_SEEN count=1
PHASE40BS_ONE_SESSION_REQUEST_OBSERVED (NOT GAME_AUTH)
PHASE40BS_COUNTS config=1 session=1 other=0
PHASE40BS_ORIGINAL_CONFIGURATION_ROUTE_RESTORED
```

### Filtered Phase 40BP Unity-native gate markers supplied with same run

```text
10-25 23:17:22.129 CGMPhase40BP: CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-25 23:17:22.174 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-25 23:17:27.166 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_DISABLED
10-25 23:17:27.367 CGMPhase40BP: CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-25 23:17:27.415 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_ENABLED
...
10-25 23:17:53.652 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-25 23:17:55.465 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_DISABLED
```

The full filtered log showed repeated five-second ACTIVE/ENABLED/DISABLED cycles as already explained in Phase 40BP. These are **CGM membership and UI-state markers**, not authentication or upstream-acceptance markers.

The helper emitted no timestamp for the `/session.php` request, so the posted logs do **not independently timestamp-correlate** a specific ENABLED marker to the exact HTTP event. The user executed the prescribed one-shot procedure, and both the original UI membership activity and one local session endpoint hit were recorded in the same test. Do not claim packet content, command bytes, or native method entry were observed from this path-only fixture.

## What this closes

1. In the **modified original APK**, the ordinary UI still obtains a valid CGM ACTIVE membership response and enables the original inner CONFIRM button (Phase 40BP evidence).
2. When the one-shot original CONFIRM UI is used with a **fictional password**, the preserved app submission path reaches local `/session.php` once. This is new evidence on top of the earlier pre-40BP localhost route test.
3. The fixture deliberately did not parse, log, forward or reuse the protected credential-bearing `?data=` query.
4. The local probe replied **HTTP 503**, did not generate any game/session success, and restored `8091` routing to the previous local synthetic PHP configuration mapping.

## Not proven

- No native `SendLogin()` function-entry instrumentation, no proof of specific network packet payload or original stage counter from this run.
- No PlayPark credential validation, relay token, GameServer/CCU acceptance, or Garden session.
- **No native sender-level fail-closed membership guard installed** yet. The Phase 40BR one-use sender guard remains a host-only/offline component.
- No successful CGM-only upstream packet preparation; an independent original intermediary can still reject `VIP` at Stage 0.
- No direct coverage of alternate/reflected calls or unsupported legacy Facebook/web-login callback.

## Next engineering target: Phase 40BT

Close the remaining gap between **UI-enabled admission** and **actual `LoginServerManager.SendLogin()` admission**. The verified original ordinary caller `LoginServerManager.Login()` invokes `SendLogin` at ARM64 RVA `0x6A8868`, then writes `isLoginProcess=1` at `0x6A8870`; `SendLogin` calls the mutating packet-counter getter at `0x6A815C`. A DENY must occur **before** original packet construction, and must also avoid setting the login-in-progress flag.

Do not splice an untested detour into the live original game. Validate real ABI/trampoline instruction relocation, Unity/IL2CPP managed string lifetime, request-generation and account binding and emulator NativeBridge behavior first. The existing Phase 40BR C guard passed 39 host-only assertions and must be integrated into actual original login submission control, not merely inferred from Button.interactable.

The user states legacy `fbLogin=true` is removed/unsupported; do not spend time restoring it. Keep it fail-closed if reachable. The relevant active route is ordinary original PlayPark CONFIRM (`fbLogin=0`).

**Privacy and rollback:** no account IDs, passwords, sessions, raw PHP URLs or packet data in this document. No live official-service test was used, no changes to Render/Cloudflare, no mutation to rollback Nox 62030 or main/default branch.

**Related checkpoints:**
- [Phase 40BP — CGM ACTIVE inside original APK](phase40bp-cgm-active-runtime-recognition-2026-10-25.md)
- [Phase 40BR — both original direct SendLogin calls and one-use guard](phase40br-two-sendlogin-paths-and-one-use-guard-2026-10-25.md)
