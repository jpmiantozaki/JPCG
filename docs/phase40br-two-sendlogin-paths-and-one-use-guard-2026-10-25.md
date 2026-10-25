# Phase 40BR — Original SendLogin boundary and one-use CGM gate

**Date:** 2026-10-25  
**Status:** offline native-source checkpoint and compiled host-only guard; **no APK patch, hook, or official login performed**.

This follows the actual [Phase 40BP CGM ACTIVE runtime recognition](phase40bp-cgm-active-runtime-recognition-2026-10-25.md). That checkpoint proves membership eligibility can enable the original inner CONFIRM button in the test APK on disposable Nox 62033; it does **not** enforce an authorization check on direct calls to `SendLogin()`.

## Two verified direct SendLogin callers

A fresh SHA-256-verified executable branch scan over the supplied original ARM64 `libil2cpp.so` identified **two** direct calls to `LoginServerManager.SendLogin` (`0x6A80C0`):

| Caller (verified in original dump.cs) | Native BL site | Argument |
|---|---:|---|
| `LoginServerManager.Login()` (RVA `0x6A86E8`) | `0x6A8868` | `fbLogin=0`, original CONFIRM |
| `LoginServerManager.<WebViewOnPageFinished>b__30_0(string)` (RVA `0x6A8890`) | `0x6A8A20` | `fbLogin=1`, web callback |

Original ARM64 module SHA-256:  
`fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`

The scan matched both direct AArch64 branch instructions. It does **not** eliminate virtual, reflected or indirect callers. The second path is a genuine source-backed reason why Button.interactable is not a complete admission boundary.

## Why a naive SendLogin no-op is unsafe

- Ordinary Login invokes `SendLogin` at `0x6A8868`, then immediately executes `0x6A886C..0x6A8870`, writing `isLoginProcess=1`. Suppressing only the sender could leave the client stuck in a login-in-progress state.
- `SendLogin` calls the **mutating** `Session.get_GetPacketCounter()` at `0x6A815C`, before writing its credential-bearing request. Denial must occur **before** that call and before any original credential packet generation.
- The web callback calls `SendLogin` independently at `0x6A8A20`, with different follow-up logic. It must not implicitly inherit permission from the ordinary UI button.
- Original username, password, version, packet framing, game-server data and genuine server verdicts must be passed through **unchanged** when a valid, unspent CGM approval permits a submission.

## New Phase 40BR offline gate contract

A separate, small host-testable C state machine now models:

1. Explicit attempt generation/epoch and current exact UTF-16 account identity.
2. A real, already-validated CGM ACTIVE response with a **maximum 5,000 ms** approval duration.
3. Rejection on missing, stale, expired, mismatched, revoked or reused approval, including A -> B -> A account switches.
4. Atomic **consume-once** permission for the matching original non-web `SendLogin` attempt.
5. Current negative/error revocation, stale-negative non-interference, logout/pause/cancel invalidation.
6. Conservative **web-mode default denial** until the web-account identity binding is independently established.
7. No password handling, packet rewriting, CCU/userSN creation, HTTP requests, or mutation of `Session.packetCount`.

**Actually executed:** host `cc -std=c11 -Wall -Wextra -Werror -pthread` compilation and **39/39 assertions passed** (including two concurrent attempts with exactly one permitted). Original binary scanner confirmed:

```text
DIRECT_BL_TO_SENDLOGIN 0x6a8868 -> 0x6a80c0
DIRECT_BL_TO_SENDLOGIN 0x6a8a20 -> 0x6a80c0
PHASE40BR_VERIFIED_TWO_ORIGINAL_DIRECT_CALLS
PHASE40BR_SENDER_CONTRACT_OFFLINE_PASS assertions=39
```

This **does not** demonstrate a native hook or live sender authorization. The source package `CGM_Phase40BR_Original_SendLogin_Boundary_Offline.zip` was created locally for review. ZIP SHA-256:
`9660071e89f919b2ac31988f7e0e38d1816df468bc3da8f103456dd7af1d5d4d`.

## Required next implementation (not yet delivered)

- Bind the validated CGM membership response and input-generation to the **actual** username used at native submission.
- Verify a safe, precise ARM64/IL2CPP interception/continuation mechanism for both caller routes **and** the common `SendLogin` entry. Nox's ARM translation and previous toy hook tests do not yet prove safe game-code patching.
- On deny, ensure the ordinary Login path skips **both** original packet creation and `isLoginProcess=1` without changing actual PlayPark authentication results.
- Observe only ephemeral stage/attempt labels, not identity strings, password, packet, relay token or complete HTTP URL.
- Review saved-session and other entry routes before representing this as all-route enforcement.
- Keep native testing and eventual APK updates confined to disposable Nox 62033, never rollback 62030.
- Distinguish CGM eligibility from the existing third-party Stage 0 VIP restriction. A valid CGM ACTIVE decision **does not** itself implement the still-missing compatible official game packet/session processor or grant Garden access.

**Repository policy:** this is a staging evidence/engineering note, not a production route change or a validated release. Neither default branch nor backend deployment is modified.
