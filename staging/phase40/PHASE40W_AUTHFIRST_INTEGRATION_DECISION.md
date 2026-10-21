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
