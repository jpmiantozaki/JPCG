# CGM Auth-First - Phase 3.4D - Generation-bound relay callbacks

Date: 2026-10-29 (Asia/Manila)
State: OFFLINE SOURCE/TEST ONLY. No new provider, game login, VIP entitlement, or Garden session.

## Why this phase is needed

The older Phase 40BY source audit identified a possible asynchronous callback lifetime race. The original mobile relay callback can be queued beyond an old HTTP request and, by the time it runs, the active LoginServer/GameServer Session object may have changed. A successful *or rejected* old response must not be delivered to a replacement socket. The existing staging `RequestLedger.php` tracked only attempt/request/stage, not a socket generation. No runtime exploit or mishandling is proven by the static audit.

Phase 3.4C already recovered a historical Android/Nox-labeled Login -> Game -> Garden response chain (52 native transport frames, plus 255 in the original PC archive); it did NOT create a fresh CGM upstream session or verify the exact mobile process producing the archive. We do not repeat cipher or Garden research here.

## New implementation

`src/GenerationBoundRelayLedger.php` is a standalone, IN-MEMORY staging-only PHP policy:

- `begin(now, ttl)` creates a fresh random local attempt identifier; no account ID, password or session token is read.
- `openSocket(attempt, LOGIN|GAME, now)` increments an attempt-scoped, service-scoped **monotonic** socket generation. Closing a socket leaves the last generation intact, so reopening it cannot reuse a previous epoch.
- `enqueue(attempt, stage, service, now, ttl)` binds a callback to its **exact original** attempt, request ID, Stage 0/1, service (0->LOGIN, 1->GAME), socket generation and expiration. It permits concurrent Stage 1 callbacks; original prior captures had two Stage 1 requests overlapping.
- `deliver(...)` matches the immutable submit-time tuple, then consumes the callback exactly once. A reconnect, close, cancellation, time expiry, or replay prevents an older callback from affecting the new session. This applies to positive and negative relay replies equally; a stale VIP denial must not disconnect the new local socket.
- No verdict ever sets `official_game_authenticated` or `authorized_garden_session` to true. Even a matched callback returns `MATCHED_LOCAL_CALLBACK_ONLY`.
- A callback from a wrong attempt, wrong stage, wrong service, or wrong generation is rejected. No decoded game data, provider session, credentials, login IDs or actual IPs are stored in output or test reports.
- `purge()` and `cancel()` clear pending work; this class is not durable or shared between PHP workers and is NOT wired to the original IL2CPP APK or live JPCG relay.

## Actual validation

The standalone source and synthetic tests were executed in PHP CLI 8.4.24:

- `tests/phase34d_generation_ledger_test.php`: 496/496 PASS; covers per-service epochs, stage restrictions, replay, wrong-attempt callbacks, duplicate delivery, simultaneous Stage1 requests returned in reverse order, reconnect, socket close/reopen, cancellation, TTL, and 100 concurrent Stage1 callbacks.
- `tests/phase34d_provider_boundary_test.php`: 21/21 PASS, using the existing Phase 40CD `ProviderHandoffGate.php` with **unconfigured** provider/link/membership/attempt stores. A locally matched callback does not permit provider admission and cannot issue a session.
- Smaller self-contained GitHub Actions tests cover 22 callback assertions plus 5 provider-boundary checks, using the unchanged staging source.
- Tests use exclusively fabricated attempt/request IDs and other synthetic metadata. No PlayPark login, real APK, Nox, Render, Neon, remote host, packet or authentication fixture used.

## Trust boundaries: what is and is not solved

A correct local callback tuple is useful for preventing stale callback confusion, but:

1. It proves no official account identity, no intermediary VIP grant, and no official LoginServer/GameServer/GardenServer authorization.
2. The real APK is still unmodified; the helper is NOT installed or wired into `RelayWebManager` and cannot stop its actual callbacks until a supported integration exists.
3. The current JPCG `public/session.php` remains a forwarder to the original intermediary (`CGM_ORIGINAL_ORIGIN=https://anlgarden.com`), whose independent VIP checks remain authoritative. CGM ACTIVE alone is insufficient.
4. The Phase 3.0 `ProviderHandoffGate` already correctly denies without an independently trusted official provider session and same-account link; this phase does not change that code.
5. The current relay may carry reversible password material in GET `?data=`; PHP metadata-only logs do not guarantee query string redaction by Apache/edge/upstream. Do not route real credentials into new test servers.
6. This ledger is in-memory and process-local. Before any permitted production integration, the real client must provide trusted attempt/socket lifecycle binding; a distributed persistent ledger and authorization semantics require independent design review.

## Required next gate - Phase 3.4E

Acquire a **documented, permitted provider or original-intermediary session interface**, or supported original-client integration path, that provides (a) stable provider-verified principal and identity link to exact ACTIVE CGM membership, (b) authorization of original game and Garden access including applicable intermediary entitlement, (c) per-attempt and session generation proof, (d) true native server-origin response-handler acceptance, and (e) privacy-audited transport/log retention.

Until those exist, the only supportable result is `PRECONDITIONS_ATTESTED_ONLY` in synthetic policy tests and `MATCHED_LOCAL_CALLBACK_ONLY` in this ledger, NEVER an issued game/Garden session. Do not falsify VIP replies, fabricate `MGRelayResponse` success, or replay captured sessions. Historical original-client success is only a reference baseline, not a reusable token.

No changes to JPCG `main`, existing production `public/session.php`, Render/Neon, original APK, experimental Nox 62033, rollback Nox 62030, or official servers.