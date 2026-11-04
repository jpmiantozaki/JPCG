# Phase 40BP — CGM ACTIVE recognized inside the original APK

**Checkpoint:** 2026-10-25, Nox local time (UTC+08)  
**Status:** USER-OBSERVED RUNTIME CHECKPOINT — original Unity CONFIRM UI eligibility control observed.  
**Scope:** disposable test-signed CGM APK, **Nox `127.0.0.1:62033` ONLY**. This is not an official game-login or Garden authorization result.

## Summary

The installed **Phase 40BP** test APK emitted `CGM_VIP_ACTIVE_FOR_CURRENT_INPUT` and, approximately 70 milliseconds later, `CGM_VIP_ORIGINAL_CONFIRM_ENABLED`. It subsequently emitted `CGM_VIP_ORIGINAL_CONFIRM_DISABLED` upon short-lease expiry, and additional ACTIVE/ENABLED pairs after fresh membership checks.

This is the first supplied **live APK runtime evidence** that the independently implemented CGM membership preflight is recognized *inside the original Unity Android application* and controls the **original inner CONFIRM Button**. The Java/native integration does not replace the original `Login()` listener.

These statements are grounded in the user's filtered `CGMPhase40BP` logcat and the exact Phase 40BP source. The assistant did not independently attach to the device or reproduce the run.

## Build and preservation evidence (user-supplied guarded build output)

- Test package: `com.DeathToB.ANLGarden`, disposable **62033**.
- Verified installed Phase 40BP APK SHA-256:
  `1941AB5D7E2D00C16B71C6B6139AE8869CF3CEEF0424245E18C6F8B319BD14E4`
- Guarded build compiled Java, ARM64 JNI gate, DEX, merged and aligned the APK, signed with the previously established test-only signer, verified the package identity, and reported **167 original APK payloads unchanged**.
- Terminal success marker: `PHASE40BP_INSTALLED_EXACT_APK_62033 (NO LOGIN TEST RUN)`. Subsequent logcat is **separate runtime evidence**, not part of the build marker.
- No source change to original `libil2cpp.so`'s `Login()` / `SendLogin()` implementation and no modified official login packet are claimed.
- **Rollback Nox `127.0.0.1:62030` was not targeted.**

## Exact privacy-safe runtime evidence

**Source:** user-posted filtered logcat with tag `CGMPhase40BP`; event names and timestamps retained, no account names/passwords/tokens/packet data/HTTP URLs.

```text
10-25 22:48:07.497 CGMPhase40BP: VIP_GATE_LIBRARY_LOADED
10-25 22:51:21.172 CGMPhase40BP: CGM_VIP_NOT_APPROVED
10-25 22:51:26.122 CGMPhase40BP: CGM_VIP_NOT_APPROVED
10-25 22:51:31.166 CGMPhase40BP: CGM_VIP_NOT_APPROVED
10-25 22:51:33.339 CGMPhase40BP: CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-25 22:51:33.409 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-25 22:51:38.347 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_DISABLED
10-25 22:51:38.479 CGMPhase40BP: CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-25 22:51:38.481 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-25 22:53:38.875 CGMPhase40BP: CGM_VIP_NOT_APPROVED
10-25 22:53:52.999 CGMPhase40BP: CGM_VIP_ACTIVE_FOR_CURRENT_INPUT
10-25 22:53:53.097 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_ENABLED
10-25 22:53:58.031 CGMPhase40BP: CGM_VIP_ORIGINAL_CONFIRM_DISABLED
```

The full pasted capture contains many repeated ACTIVE/ENABLED/DISABLED cycles between 22:51:33 and 22:54:08, not merely one isolated pair.

## Interpretation tied to the implemented source

| Runtime marker | Source-level meaning | Limitation |
|---|---|---|
| `VIP_GATE_LIBRARY_LOADED` | Java preloader loaded the dedicated native gate library | Does not prove the Button exists yet |
| `CGM_VIP_NOT_APPROVED` | Membership preflight yielded no usable current approval | Does **not** distinguish INACTIVE vs network/parse error vs changed identifier |
| `CGM_VIP_ACTIVE_FOR_CURRENT_INPUT` | HTTPS membership response passed strict schema, request ID, ACTIVE, expiry and TTL checks for the current candidate | Is **CGM eligibility**, not official PlayPark authentication |
| `CGM_VIP_ORIGINAL_CONFIRM_ENABLED` | On the original Unity loop, the gate received a positive native Button-setter status after matching the current InputField value | Log indicates setter invocation/status, **not** a submitted login or independent visual proof |
| `CGM_VIP_ORIGINAL_CONFIRM_DISABLED` | The previously enabled original Button changed to not-approved/disabled state | Does not identify the reason without additional evidence |

**Native scope:** the gate locates `Canvas/Login/imgLoginBase/btnLogin`, reads `LoginServerManager.txtUsername`, and calls `Selectable.set_interactable`. Original persistent `Button.onClick -> LoginServerManager.Login()` is not replaced. Java issues an email-only `POST` to `https://jpcg.onrender.com/authfirst.php` using `cgm-email-membership-v1`. No password is part of the CGM membership request.

**Five-second oscillation explained:** Phase 40BP intentionally caps an ACTIVE approval at **5,000 ms** and only starts a new request after its approval expires. Thus the repeated DISABLED -> ACTIVE -> ENABLED cadence is consistent with expected fail-closed expiry/recheck behavior; it does not show the underlying membership repeatedly changing status.

**Separate Phase 40BQ:** source-only proposal to renew within the existing five-second lease (2,000 ms before expiry), with **10/10 new renewal and 13/13 response-contract Java tests passed offline**. **Not installed; no Nox runtime proof for Phase 40BQ.** This is an optional UX/stability refinement, not sender-level enforcement.

## What is PROVEN, and what is NOT

### Supported by the supplied run

1. The installed modified original APK loads the CGM VIP gate library.
2. It receives and classifies an ACTIVE result from the configured CGM membership contract.
3. It controls the original inner CONFIRM Button's interactable state via Unity/JNI with in-app account-value comparison.
4. Short-lease expiry revokes interactability; fresh ACTIVE checks can restore it.
5. The original app's game authentication binaries and persistent CONFIRM listener were preserved by the staging build, according to its parity checks.

### Not demonstrated; do not claim otherwise

1. **No original `Login()` or `SendLogin()` submission was attempted or accepted** in this capture.
2. **No PlayPark credential authentication, GameServer/CCU session, or Garden session was established.**
3. The original relay/upstream can still issue `VIP` rejection at Stage 0. CGM ACTIVE by itself does **not** override that external restriction.
4. **No sender-level fail-closed guard** has been installed. Direct `SendLogin`, alternate web/saved-session routes and other entry points remain unverified.
5. `CGM_VIP_NOT_APPROVED` alone cannot be labeled a confirmed INACTIVE or account-denial response.
6. The actual UI was not independently visually verified in this specific log; the Unity native setter and state marker are the available evidence.
7. No production deployment, backend modification, default-branch change or official-service login is part of this checkpoint.

## Next engineering target — Phase 40BR: enforce at the sender boundary

The ordinary original CONFIRM **UI** now recognizes CGM eligibility. Next, make the **original native credential submission** fail closed unless the currently submitted identity has a fresh, unspent CGM ACTIVE decision.

Required invariants before `SendLogin` is allowed:

- Match the **current actual submitted identity** (not a stale cached email) to the approved membership identity.
- One-use, generation-bound approval; invalidate on username edit, cancel, logout, scene/activity transition, expiry and superseded replies.
- Block/deny non-ACTIVE, timeout, malformed, expired, mismatched, or duplicate attempts **without fabricating official game acceptance or changing credentials**.
- Cover alternate direct submit routes; UI-level `interactable=false` is not a security boundary.
- Avoid calling `Session.get_GetPacketCounter()` for logging: it increments the packet counter.
- Keep any registration/detour on **disposable test builds only** until native calling convention, hook/trampoline safety and reconnection behavior are verified. Original game's login/session success remains determined by its real servers.
- First check should be offline binary/ABI hook feasibility and a bounded synthetic boundary test. Do **not** submit official credentials to the local PHP/TCP fixture; do not touch rollback device 62030.

**Critical independent obstacle:** the current JPCG `public/session.php` still forwards original relay packets; a CGM membership preflight is not a replacement for official protocol/session processing. Do not describe Phase 40BP or the next native gate as CGM-only Garden access.

## Provenance and repository policy

- Local source package: `CGM_Phase40BP_VIP_Apk_Gate_Source_and_Guarded_Build.zip`, verified by separately supplied local build/install output.
- Optional local-only revision: `CGM_Phase40BQ_Early_Membership_Renewal_Source.zip`; not installed.
- Project staging branch: `feat/phase40-staging-provider-interface`.
- This document is **an evidence log only**. It changes no PHP route, backend, Unity APK, game account, production configuration or default branch.
