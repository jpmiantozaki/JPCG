# Phase 40W — Auth-First integration decision and acceptance contract

**Status:** Design checkpoint only. Not a deployable CGM APK. No production/backend or user-device modifications in this commit.

## Phase 40V disposition

On disposable Nox `127.0.0.1:62032`, Frida x64 attached and detached from the running CGM Unity app (PID 3363) successfully. Export inspection of the Houdini host module found 44 symbols, with only `NativeBridgeItf` matching the guest/trace/bridge keyword filter. This AOSP-defined native-bridge callback provides a library/JNI translation integration interface, not a documented way to instrument arbitrary IL2CPP guest-managed methods. No supported translated guest-entry interception interface is established.

Phase 40K, 40L and 40P-R1 proved synthetic guest detour / pass-through only; Phase 40M, 40N, 40O and 40Q proved original Unity UI method identity and live guest image bytes; Phase 40S, 40T, 40U and 40V proved host debugger connectivity and visibility. None of these proves a production-safe CGM authorization gate.

## Confirmed server contract

`public/authfirst.php` handles CGM membership only, with `Cache-Control: no-store`. Strict W format:

```json
{"schema":"cgm-email-membership-v1","email":"member@example.com","request_id":"unique-request-id"}
```

Returned values include `schema`, `request_id`, `status` among `ACTIVE`, `INACTIVE`, `ERROR`, `server_time`, `membership_expires_at`, and `ttl_ms`. Only fresh, unexpired `ACTIVE` may permit *attempting original authentication*. The legacy contract returns `scope=CGM_MEMBERSHIP_ONLY`, `eligible_to_attempt`, and explicitly `game_authorized=false`.

`public/_cgm_session_bridge.php` still relays authentication/session/analytics to the designated original upstream server and preserves its responses. Therefore a CGM `ACTIVE` result is **not PlayPark or ANLGarden VIP entitlement**, and cannot create or substitute a valid game/Garden session.

## Source- or provider-supported integration prerequisite

One of these genuine mechanisms is needed *before implementation within the original client*:

1. A matching authorized Unity source project with ability to rebuild and sign the CGM client, preserving and calling the original official login and Garden session implementation.
2. Publisher-provided/owner-approved extension or SDK with a documented callback **before original credential submit** and a supported way to defer then resume original submission **exactly once**. The callback must cover the inner `Login()` binding and any other supported credential submit paths.

Attaching Frida to x64 Houdini is not that extension. Redirecting native `Login()`/ `SendLogin()` or faking VIP responses without the intermediary provider's approval is not a production-compatible substitute.

## Integration behavior to implement only after prerequisite

1. Read only the login identifier (email) from the existing UI. Never intercept, log or transmit the password or original PlayPark credentials to the CGM membership backend.
2. On original credential submit, prevent original native `Login()` from running until membership check completes. Do not assume adding a second Unity Button listener suppresses its serialized callback.
3. Use one unique request ID per attempt. Bind response to exact original identifier, attempt generation and session lifetime. Reject stale/mismatched responses.
4. Permit exactly one original official `Login()` submission when a fresh unexpired `ACTIVE` result is received, using only original PlayPark authentication and session packets.
5. For `INACTIVE`, `ERROR`, timeout, expiry, malformed/duplicate/racing responses or changed identifiers, fail closed and allow legitimate correction/retry. Preserve existing redemption UI without fabricating VIP status.
6. Any official or intermediary downstream authorization failure stays a failure; do not rewrite server responses or forge Garden sessions.

## Evidence required for completed APK

- Source/SDK authorization and matching build provenance.
- Coverage of all in-scope credential submission paths and exactly-once dispatch under reconnects and retries.
- No official `Login()` before fresh `ACTIVE`; no credentials in membership requests.
- Correct fail-closed cases with sanitized logs, no leakage of emails, passwords, cookies or tokens.
- Unchanged upstream login, valid official Game session and separately authorized Garden access.
- Reproducible signed APK test on disposable Nox 62032 and documented rollback, without touching 62030 or 62031.

**Current missing input:** provider/source-extension access and the provider's approval that a CGM membership grant can be used as the permitted pre-login eligibility criterion. Do not infer these permissions from a working synthetic trampoline.

## User clarification — binary-only CGM target; no official ANLGarden release (2026-10-21)

The user states explicitly:
- No current access to the original **BASE or MOD APKs**. Do not ask for these as a prerequisite to continue, and do not assume they are present on the Windows host despite historical references.
- There is **no official ANLGarden/CGM mobile release**. ANLGarden was independently made as a **third-party phone access tool** for an existing Garden server, not as an official PlayPark-developed mobile client.
- The installed **CGM APK** on disposable Nox `127.0.0.1:62032` was identified and verified in Phases 40J–40Q and remains the local experimental binary target.

**Decision correction:** do not imply that a first-party ANLGarden SDK or an official ANLGarden release ought to be obtainable or is necessary for research. Continue *binary-only compatibility and integration feasibility* on the CGM APK only, in disposable testing and without modifying original servers. Source- or extension-based integration is an alternative **if actually available**, not a prerequisite to all progress. Neither synthetic hooking nor CGM ACTIVE membership proves a downstream Garden authorization grant. Preserve genuine PlayPark authentication, do not forge VIP/session authorization, and distinguish inability to bypass an external entitlement check from the feasibility of implementing a local pre-login membership gate.

A viable native interception integration has not yet been established for the Houdini-translated Unity app. The remaining engineering question is whether CGM can be safely modified in the local test copy with a genuine fail-closed pre-login gate, without intercepting or forwarding passwords to membership services, without changing upstream authorization decisions, and with a fully documented restore/rollback plan. Do not claim a working APK.

## Confirm-vs-VIP precedence — user-controlled A/B observation (2026-10-21)

**User-observed exact comparison:** Using the *same real account email and the same intentionally wrong password* through the original second credential CONFIRM action:
1. Before that email/account had redeemed VIP, the CGM APK went to the Redeem UI instead of showing an incorrect-credentials error.
2. After VIP redemption on that email/account, submitting the same email and intentionally wrong password produced the official incorrect-credentials error.

This is strong empirical evidence that the outcome presented to the user is gated/prioritized by VIP status, and that the VIP rejection/redemption transition does **not require displaying a successful password validation**. Account ID is likely used to determine VIP eligibility. This does **not** alone prove server request order, that the exact raw email is the lookup key (identifier may be normalized/prefixed), or whether official authentication was attempted before an error was masked. Do not ask the user to repeat live wrong-password tests just to reproduce this already observed behavior.

**Critical next static tracing priority (distinct VIP paths):**
- Inner button `Login()` RVA `0x6A86E8` calls `SendLogin(...)` RVA `0x6A80C0`, storing account identifier into shared context `+0xB0`.
- Relay path `RelayWebManager.RelayServerData()` Ghidra `0x007B04CC` with HTTP callback Ghidra `0x007B088C`. Prior decompile: on relay error and response message `VIP`, invokes `VIPManager.ReceiveNoVIPData`; successful relay response forwards packet bytes to original Login/Game sessions. Analyze whether this is the exact stage that short-circuits wrong-password feedback.
- Separate async `RelayWebManager.RelayVerifyVIP()` Ghidra `0x007B0628` and callback `0x007B0D50`; this also can disconnect/restore/show Redeem UI on verification failure. Do NOT conflate it with relay session VIP rejection.
- Client login packet handler `LoginServerManager.OnPacketReceived` Ghidra `0x007A738C` is relevant to password-error display when VIP succeeds.
- Need prove path from CONFIRM through relay result handling and distinguish errors without logging plaintext passwords, packet bodies, cookies, session keys, or account identifiers.

**Decision:** Next inspect the previously identified original **relay HTTP response callback** `0x007B088C` in Ghidra, with references to `ReceiveNoVIPData`, and compare against separate `0x007B0D50` VIP-verification callback. Use offline decompilation as primary evidence, not unneeded live login experimentation. Preserve upstream entitlements; do not rewrite relay `VIP` rejection into success.
