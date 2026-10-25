# Phase 40BV — Live original Login method-pointer witness (offline-prepared)

**Checkpoint date:** 2026-10-26  
**Status:** New diagnostic source package verified **offline only**; not built, signed, installed or executed on Nox in this checkpoint. Previous **Phase40BP signed APK successfully restored and exact-hash verified on experimental 62033**.

## Why 40BV exists

Prior Phase40BU changed exactly 4 bytes at original ARM64 RVA `0x6A8868` (`BL SendLogin` -> `B Login epilogue`) in the signed APK. The user verified the Phase40BU APK was installed and an owned path-only fixture unexpectedly recorded one local `/session.php` request. It remains unknown whether the runtime used that modified instruction, another execution route, or a translated-code alias/cache. Native packet contents and official authentication were never captured or tested.

**ABI correction:** Phase40BP's in-APK CGM VIP JNI library only exists at `lib/arm64-v8a/libcgm_authfirst_gate.so`, yet the real app successfully logged `CGM_VIP_ORIGINAL_CONFIRM_ENABLED` through that native setter. Thus an ARM32-only runtime explanation of the earlier 40BU result is unlikely. The precise guest code used for `Login()` nevertheless needs runtime attestation. Android NativeBridge/guest mappings may have multiple aliases; this is a hypothesis, not a proven cause.

## What Phase40BV provides

A small source-only artifact `CGM_Phase40BV_Guest_Login_Witness.zip`; SHA-256 `1c9c10778c2534c9542f9b656afa4d3bc53c4eac52bdcdfe708c72151282025c` (**26,682 bytes**). No original APK or proprietary native binary included.

It **reuses the verified Phase40BP Unity-loop Java/JNI gate**, and adds three passive native readers:

- `nativeLoginCodeWitness()`: resolve the actual managed `LoginServerManager.Login()` MethodInfo methodPointer; verify it belongs to the expected guest `libil2cpp.so` at RVA `0x6A86E8`; read the expected entry instruction and classify the instruction at `0x6A8868`. Classifications distinguish original BL, test DENY branch, missing/mismatched method pointer, unreadable/unexpected bytes, and different guest-module alias.
- `nativeLoginProgressWitness()`: read original manager `isLoginProcess`, no setter.
- `nativeLoginCounterWitness()`: read current `DeathToB.Session.packetCount` field via IL2CPP runtime field access, with an internal ephemeral session-generation label. **Never call `Session.get_GetPacketCounter()` because it mutates the value.**

Events are coarse labels only: `LOGIN_METHOD_DENY_BRANCH_GUEST_BYTES`, `LOGIN_METHOD_ORIGINAL_GUEST_BYTES`, `LOGIN_METHOD_POINTER_MISMATCH_STOP`, `LOGIN_PROGRESS_SET/CLEAR`, `LOGIN_SESSION_COUNTER_ADVANCED_SAME_SESSION`, etc. No account ID, raw pointer, password, packet, relay URI, token or official response is logged.

**Important:** The observer is read-only, but this test-only APK also preserves the exact Phase40BU **unconditional DENY** one-instruction branch to probe that case. It is **not** an active conditional CGM sender guard and must be restored to Phase40BP afterward.

## Offline verification actually performed

- Original native SHA-256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8` checked.
- Exact four-byte ARM64 patch constructed from original ELF; no other ELF bytes changed.
- 10/10 targeted Python tests passed, including a synthetic ZIP payload-parity test: all original APK entries remain unchanged **except** `classes2.dex`, `lib/arm64-v8a/libcgm_authfirst_gate.so`, and patched `lib/arm64-v8a/libil2cpp.so`.
- 13/13 previously established CGM membership-response decision tests passed with the updated Java.
- C strict host/JNI syntax and Java 8-source compilation passed; Python code compilation passed.
- New file checksums, archive CRC and ZIP manifest verified.
- **Not verified:** Actual Windows Android NDK and D8 build, test-key signing, Nox installation, guest method-pointer reads, NativeBridge alias behavior, direct `Login()` execution, game authentication, Garden access.

## One bounded next check, no credentials

Build/install from known-good Phase40BP **only if currently exact installed** on **62033**, then launch the test APK and observe filtered `CGMPhase40BV` logcat tags **before pressing CONFIRM**. If live code classifies as `DENY_BRANCH`, report that marker and decide whether a separate one-shot isolated click test is warranted. If `ORIGINAL_GUEST_BYTES`, `POINTER_MISMATCH` or `UNVERIFIED` occurs, stop; do not submit credentials or relax checks. Use the bundled exact-hash restore script to return to Phase40BP. This first check needs no PlayPark account, original password or game session.

This method-pointer memory observation can show guest bytes **but not that the method was executed**. It is a prerequisite for a later execution-correlation test; one `/session.php` request alone remains insufficient.

**Boundaries:** no production or default-branch changes, no official traffic, no backend mutation, no host-side Frida attach, no APK install in this checkpoint, no device `62030` access. Legacy Facebook login is unsupported; current focus is ordinary PlayPark CONFIRM. Independent authentic game-session upstream remains unresolved.

## LIVE RUNTIME UPDATE — user report, 2026-10-26 (UTC+08)

The user installed/launched the Phase40BV diagnostic APK and provided the following narrow `CGMPhase40BV` logcat events from **two distinct app processes**:

```text
10-26 01:18:35.414 PID=4922 CGMPhase40BV: LOGIN_METHOD_DENY_BRANCH_GUEST_BYTES
10-26 01:19:05.249 PID=4922 CGMPhase40BV: LOGIN_PROGRESS_CLEAR
10-26 01:20:02.451 PID=5139 CGMPhase40BV: LOGIN_METHOD_DENY_BRANCH_GUEST_BYTES
10-26 01:20:31.176 PID=5139 CGMPhase40BV: LOGIN_PROGRESS_CLEAR
```

**Runtime verdict — PARTIAL PASS / code-pointer evidence:** Twice, actual `LoginServerManager.Login()` IL2CPP MethodInfo's `methodPointer` was located in the expected `libil2cpp.so` mapping at original RVA `0x6A86E8`. The call-site bytes at `0x6A8868` matched the installed Phase40BV deny branch. The marker emitted by the code-witness distinguishes the canonical module mapping from alias/mismatch states. This substantially reduces (but does not mathematically eliminate) the hypothesis that the wrong/unpatched ELF was being sampled. The user restarted the app between observations: PID **4922** then **5139**; do not count these as two login attempts.

**Progress flag interpretation:** The user explicitly reports `LOGIN_PROGRESS_CLEAR` appears when yellow LOGIN is shown, **before credential CONFIRM**. In the current Java source, `lastProgressWitness=-1`, so the first successful field read (0) emits `LOGIN_PROGRESS_CLEAR` even if the field has not changed. Thus it is a **baseline state observation**, not proof `Login()` executed, denied, or was reset by a callback. No `LOGIN_PROGRESS_SET` or `LOGIN_SESSION_COUNTER_ADVANCED_SAME_SESSION` appears in the four supplied lines; that limited excerpt does not prove neither event could occur in a future click.

**Unresolved:** No CONFIRM click or local path-only request count accompanies this specific two-process witness. The prior Phase40BU single `/session.php` hit is a separate run. Method-pointer byte observation is not an execution trace: guest translation/caching, alternate caller routing or previously queued traffic are still unassigned. Do **not** declare sender denial successful.

**Next meaningful and bounded test** (only while the verified Phase40BV experimental APK is installed): a single user-witnessed inner CONFIRM click using CGM ACTIVE email + fictional password through the existing isolated PHP8094 / TCP18992 + path-only one-shot localhost `/session.php` rejecting fixture. Record the `CGMPhase40BV` login-progress and session-counter transition labels alongside the fixture's `config/session/other` counts. If the click or enabled state is not witnessed, zero session hits is inconclusive. The old Phase40BU probe is path-only and does not verify installed APK hash or report failure in exit code; check markers and installed identity, not its return code. No raw URL, account, token, packets or password in logs. Restore Phase40BP with Phase40BV's exact-hash script afterward. **This proposed click test has not been run** as of this log.

No official game login, independent upstream, Garden session, production release or rollback 62030 access is implied.

## Next run reported: zero local session requests but click not yet established (user screenshot, Oct 26)

The user supplied a screenshot of the **existing Phase40BU path-only probe run against the current Phase40BV experiment**. The probe printed:

```text
PHASE40BU_CONFIG_REQUEST_SEEN
PHASE40BU_ZERO_SESSION_REQUESTS_IN_OBSERVATION_WINDOW
REQUIRES_USER_REPORT_CONFIRM_CLICKED_WHILE_ENABLED
PHASE40BU_COUNTS config=1 session=0 other=0
PHASE40BU_ORIGINAL_CONFIGURATION_ROUTE_RESTORED
```

**Interpretation: INCONCLUSIVE, not deny-branch PASS.** The local synthetic configuration request was observed, no `/session.php` endpoint was observed within the 75-second default window, and the probe reported restoration of previous route `8091 -> 8094`. The test harness cannot detect whether the user clicked CONFIRM. Its success/zero-hit marker is emitted for `config=1, session=0` even when no login submission was attempted.

The filtered log window in the screenshot contains the earlier Phase40BV method-pointer markers from **PID 4922 and PID 5139**, but the newest process is **PID 5601**, for which the only shown event is `CGMPhase40BP: VIP_GATE_LIBRARY_LOADED` at `01:31:14.760`. No `CGMPhase40BV: LOGIN_METHOD_DENY_BRANCH_GUEST_BYTES`, `LOGIN_PROGRESS_CLEAR/SET`, `CGM_VIP_ACTIVE_FOR_CURRENT_INPUT` or `CGM_VIP_ORIGINAL_CONFIRM_ENABLED` is visible **for PID 5601** in the supplied screenshot. The application displays a credential form, which does not establish a valid membership approval or a CONFIRM click.

**Do not merge old-PID method-pointer evidence with current-PID endpoint counts.** The earlier two process witnesses remain verified on their own. Before assigning a successful native deny result to this latest run, require the user's statement that CONFIRM was actually clicked while enabled **within the probe window**, plus corresponding current-PID gate/witness events if available. If not clicked or not enabled, no new APK build or official login test is justified; the zero count simply shows an idle synthetic fixture. This distinction matters especially because the preceding Phase40BU experiment reported one unexpected `/session.php` request.

No credentials, token, URI query or packet contents were captured. No production change or actual official PlayPark session is demonstrated. The working Phase40BP should be restored via the existing exact-hash Phase40BV restore script once the disposable diagnostic is complete.

## User clarification: prior zero-request probe was idle; later PHP session hit

User explicitly clarified: **“I didn't do anything while the probe is checking”** (2026-10-26). Therefore the previous `PHASE40BU_COUNTS config=1 session=0 other=0` cannot be interpreted as deny-path success; a manually witnessed CONFIRM click was absent.

Separately, the user supplied filtered `CGMPhase40BP` events **for PID 5601**, recorded after that probe:

```text
10-26 01:36:27.105 CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-26 01:36:27.190 CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-26 01:36:32.125 CGM_VIP_ORIGINAL_CONFIRM_DISABLED
10-26 01:36:32.246 CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-26 01:36:32.271 CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-26 01:36:37.304 CGM_VIP_ORIGINAL_CONFIRM_DISABLED
10-26 01:36:37.446 CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-26 01:36:37.454 CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-26 01:36:40.202 CGM_VIP_ORIGINAL_CONFIRM_DISABLED
```

Existing **synthetic PHP 8094** later emitted the path-only line:

```text
[Mon Oct 26 01:36:42 2026] [CGM_CONFIG_TEST] unavailable_route path=/session.php
```

The latter is not from the completed 8095 one-shot probe. The probe restores 8091 -> 8094 after its bounded observation, and the original PHP synthetic router has no supported game session route. The trace shows a local endpoint hit, not official acceptance. Because no precise click/route-generation correlation was captured for the later PHP request, **do not infer its initiating native method, submission time, or acceptance outcome**. `CGM_VIP_ORIGINAL_CONFIRM_DISABLED` at 01:36:40 does not by itself prove the endpoint hit resulted from an enabled-button click.

**Next bounded action:** only if Phase40BV remains verified installed and synthetic PHP8094/TCP18992 are active, run the existing 62033-scoped BU path-only probe **one more time with a deliberate single CONFIRM click while `CGM_VIP_ORIGINAL_CONFIRM_ENABLED` is active and the 8095 probe says ARMED**. Use a fictional password and return only path counts, same-process witness markers and user-confirmed click. The 75-second default probe timer can be extended to 120 seconds by `--seconds 120` (bounded script limit). If no current-PID `LOGIN_METHOD_DENY_BRANCH_GUEST_BYTES` marker, stop instead of assuming the old PID's witness applies. Afterward restore the exact working Phase40BP with the existing 40BV restore script; no rebuild, genuine credentials, official service test, raw URL logs or rollback 62030 access.

**Status remains INCONCLUSIVE pending a witnessed click and contemporaneous labels.**

## Repeated probe: one local session request; current-process native witness absent from excerpt

**User-supplied runtime evidence** (Oct 26, local UTC+08, from disposable Nox 62033):

```text
PHASE40BU_SAFE_FIXTURE_ARMED_N0X62033
PHASE40BU_CONFIG_REQUEST_SEEN
PHASE40BU_SESSION_ENDPOINT_REQUEST_SEEN count=1
PHASE40BU_DENY_ONLY_FAILED_SESSION_REQUEST_SEEN
PHASE40BU_COUNTS config=1 session=1 other=0
PHASE40BU_ORIGINAL_CONFIGURATION_ROUTE_RESTORED

10-26 01:38:27.610 PID 5967 CGMPhase40BP: VIP_GATE_LIBRARY_LOADED
10-26 01:39:28.487 PID 5967 CGMPhase40BP: CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-26 01:39:28.552 PID 5967 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-26 01:39:33.652 PID 5967 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_DISABLED
10-26 01:39:33.761 PID 5967 CGMPhase40BP: CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-26 01:39:33.827 PID 5967 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-26 01:39:35.068 PID 5967 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_DISABLED
```

**Interpretation:** The path-only synthetic fixture saw exactly one session endpoint and intentionally rejected it; route restoration was reported. This **fails the deny-only zero-request expectation** but is not proof the patched branch executed. The user has **not explicitly confirmed a manual CONFIRM click in this message** and the probe lacks timestamps for the session hit.

**New forensic clue from actual shipped BV Java:** every fresh process's first successful `sampleOnUnityLoop()` call executes `nativeLoginCodeWitness()` and, since `lastCodeWitness=-1`, emits a `CGMPhase40BV: LOGIN_METHOD_...` status before any `CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_ENABLED` marker (both happen in the same Unity-loop method; lines 187–219 in BV source). The posted **PID 5967 excerpt has no `CGMPhase40BV` markers**. That could mean that only the `CGMPhase40BP` log tag was collected/shared, that a BP/pre-BV build is running, or that the excerpt is incomplete. It should **not** be interpreted as a verified live BV deny instruction for PID 5967.

Earlier BV method-pointer attestations were obtained from PIDs **4922 and 5139** and cannot be transferred automatically to PID 5967. The original `VIP_GATE_LIBRARY_LOADED` tag exists in BP *and* BV and cannot identify the installed build.

**Next step (no repeat login):** query retained **`CGMPhase40BV` logcat for PID 5967** and verify the *currently installed* APK's SHA-256 against Phase40BP `1941AB5D7E2D00C16B71C6B6139AE8869CF3CEEF0424245E18C6F8B319BD14E4` and the local known Phase40BV signed artifact. Read-only ADB `pm path` and `pull` only to 62033; preserve unique local inspection file names. If the application has been replaced since that run, present hash only proves current state, not historical state. **Do not run another CONFIRM test, patch or unguarded restore until build identity is resolved.**

No official game authentication, successful server state or new independent upstream is demonstrated.

## Correction after installed-APK hash check: Phase40BP is currently installed

The user explicitly confirmed the Oct 26 repeated 8095 fixture test involved **exactly one original CONFIRM click while enabled**. The path-only fixture reported `config=1/session=1/other=0`. The provided filtered `CGMPhase40BP` log for PID 5967 shows an ACTIVE result followed by CONFIRM enabled, with no matching same-PID `CGMPhase40BV` marker in the pasted excerpt.

**Fresh read-only installed-build attestation:**

```text
INSTALLED_SHA256=1941AB5D7E2D00C16B71C6B6139AE8869CF3CEEF0424245E18C6F8B319BD14E4
RESULT: PHASE40BP_INSTALLED
```

This exact SHA-256 is the known-good Phase40BP signed APK, **not** the Phase40BV patched guest witness. Hence the most straightforward explanation for the `/session.php` request is the **original, allowed Phase40BP submission path**, not a failed executed Phase40BV deny branch. The check establishes **the currently installed APK**, not a historical absolute timestamp of which build executed during PID 5967. The user has not reported another install between the test and this hash inspection; preserve that distinction instead of claiming certainty about the past from a current hash.

**Frida/logcat correction:** the follow-up command `adb -s 127.0.0.1:62033 logcat -d -s CGMPhase40BV:I '*:S'` emitted **`- waiting for device -`**. The subsequent `NO_BV_MARKERS_FOR_PID_5967` was derived from empty/unavailable output; it is **not a valid negative runtime observation**. If relevant in another test, verify ADB device state and the current process before interpreting an empty filtered log.

**Revised status:** The Phase40BU/Phase40BV original deny-branch execution remains **unresolved**, but **the latest session=1 observation is no longer evidence that the BV branch failed**. Prior method-pointer deny bytes were verified in the separate PIDs 4922 and 5139 only. Phase40BP is installed and is the safe restored baseline; do **not** run the Phase40BV exact-hash restore script against BP unless needed (it can return ALREADY_RESTORED).

**Next action (do not repeat past build):** if a single final deny-only proof is needed to justify native sender-gate integration, use the already built `Phase40BV_SIGNED_TEST_ONLY.apk`, verify its hash and current BP installed identity, install it **once** on disposable Nox62033 (no build or folder cleanup), capture a fresh same-PID `LOGIN_METHOD_DENY_BRANCH_GUEST_BYTES` marker, then conduct exactly one isolated CONFIRM test with CGM ACTIVE email + fictional password. Record `config/session/other`, method-pointer and progress/counter markers in that same PID, and restore exact BP. No real login credentials or live official service request, no modification of rollback 62030. Until such a correctly attested trial exists, no conditional sender-level native hook is justified.
