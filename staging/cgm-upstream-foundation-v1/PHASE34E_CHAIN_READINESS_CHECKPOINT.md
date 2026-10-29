# CGM Auth-First - Phase 3.4E: Original Mobile Chain Readiness

Date: 2026-10-29, Asia/Manila
Status: STAGING/OFFLINE ONLY. Internal chain validation complete; external provider authorization is NOT available.

## What was reused (not rebuilt)

- Phase 3.0 trusted account link + ACTIVE exact CGM membership + authorized provider verifier + atomic one-use attempt policy, in `ProviderHandoffGate.php`.
- Phase 3.4D connection-generation-aware `GenerationBoundRelayLedger.php`: Stage0 -> LOGIN, Stage1 -> GAME, with original attempt/service/socket epoch, request TTL and single-use callbacks.
- Phase 3.4C historical Android/Nox-labeled response order and its seven native-handler milestones. Original captured data is an observational reference, not proof that an independently issued CGM session exists.
- Phase 4.24-4.28 original PC Garden snapshot/observer and Garden inventory mapping. No redundant Garden codec or Ghidra work.

## New source: OriginalMobileChainCoordinator.php

This class composes the existing Phase34D ledger with the existing Phase30 gate. A single locally generated 128-bit attempt ID is reused across Stage0, Stage1 and eventual provider evaluation. The source accepts no passwords, login-frame bytes, remote IP addresses, relay session tokens, or official server packets.

All seven known native handler milestones are represented as DECLARED LOCAL OBSERVATIONS (not verified authority):

1. `LOGIN_ZERO_RESULT` - original LoginServer handler, RVA 0x6A738C.
2. `VERIFY_CCU_ACCEPTED` - original GameServer CCU handler, RVA 0x69AA08.
3. `GAME_ZERO_RESULT` - original GameServer login handler, RVA 0x69AB94.
4. `USER_INFO_HANDLER_OBSERVED` - original user-info handler, RVA 0x69AEB8. Its full result grammar is unresolved; seeing the handler is NOT a validated user-info success.
5. `CHANNEL_TO_GARDEN` - original channel/Garden connect branch, RVA 0x69AD18.
6. `GARDEN_LOGIN_ZERO_RESULT` - original Garden login handler, RVA 0x66E104.
7. `GARDEN_LOAD_ZERO_RESULT` - original Garden-load handler, RVA 0x66E548.

A marker advances ONLY in declared event order, for the current attempt's correct service and socket generation, after its corresponding locally correlated relay callback. LoginServer can close normally after success-style handoff. GameServer or GardenServer loss cancels a pending admission; GameServer/Garden reopen resets downstream local markers. A stale callback, replay, expired attempt or wrong stage/service/epoch is rejected.

Only after all seven markers and both Stage0/Stage1 callback correlations are present can `finalize()` consult the separately trusted `ProviderHandoffGate`. The gate has a separate verifier, server-stored verified account-link lookup, server-side exact membership lookup and global one-use attempt store. The production-safe default provider verifier always refuses to attest.

### Crucial output semantics

- A locally matched callback returns `MATCHED_LOCAL_CALLBACK_ONLY`, not authentication.
- A locally recorded handler returns `LOCAL_NATIVE_MARKER_RECORDED` and `official_origin_verified=false`.
- Without a real authorized provider the end-to-end result is `DENIED`, usually `PROVIDER_UNCONFIGURED_OR_UNVERIFIED`.
- A fully synthetic provider fixture may cause `PROVIDER_POLICY_PRECONDITIONS_ATTESTED_ONLY`. Even then: `session_issued=false`, `game_packet_generated=false`, `garden_authorized_by_cgm=false`.
- The coordination object is IN MEMORY, source-only and NOT connected to the original IL2CPP APK, live ingress, PostgreSQL, PlayPark, Render or any official game server.

## Tests run (all fictional)

- `tests/phase34e_chain_test.php`: **442** PHP synthetic assertions. Includes a complete seven-marker local sequence, out-of-order concurrent GameServer callback delivery, correct and wrong socket epochs, old request rejection on reconnect, expiry, account mismatch, missing official Garden permission, separate third-party entitlement denial and explicit non-session outputs even with fictional provider approval.
- `tests/phase34e_ci.php`: **323** assertions, including 100 independent short attempts that never produce authorization.
- Existing `tests/phase34d_generation_ledger_test.php`: **496** synthetic assertions on the retained unchanged Phase34D ledger. `tests/phase34d_provider_boundary_test.php`: **21** synthetic assertions.
- All sources are PHP 8.4.24 syntax-clean in local host. There is NO actual mobile login in these tests.
- ZIP integrity and per-file SHA manifest validated after clean extraction (details in `evidence/LOCAL_VALIDATION.txt`).

## What does NOT work yet

The external independent mobile session issuer/verifier is STILL MISSING. The original third-party intermediary can reject non-VIP membership separately; CGM ACTIVE cannot override or replace it. There is no provider-approved processor for the original `/session.php` Stage0/Stage1 protected packets. The real APK has not been changed to emit generation-bound markers or call this coordinator. A historical PC or Nox packet proves a past original response shape, not a new authenticated CGM session.

The original request could carry reversible credentials in GET `?data=`. Do not send real passwords to experimental relays. Apache/edge/CDN/APM logging and upstream privacy still need review.

## Next true go/no-go gate - Phase 3.4F

A provider/operator must supply **one** documented authorized server-side provider contract, which binds a verified official account subject (issuer+subject+audience) to the exact claimed PlayPark login and exact ACTIVE CGM membership key, and explicitly attests genuine game and Garden authorization and any external mobile intermediary entitlement, with stable attempt ID and expiry. The compatible, permissioned original-client/session continuation also must be established. No such contract was included in the inspected sources or public documentation. The existing `UnconfiguredProviderVerifier` will continue denying until one exists.

Once there is a real authorized provider interface: add a narrowly scoped adapter implementing `AuthorizedProviderVerifier` to obtain verified attestations server-side. Ensure the authorized session/entitlement is accepted by the original LoginServer/GameServer/GardenServer. Require a permissioned original-client integration and privacy-audited transport. DO NOT fake `MGRelayResponse` positive status, reuse PCAP tokens, forge CCU/UserSN, or rewrite `VIP` to success.

Alternative already functioning reference for *read-only* Garden state: Phase 4.28 PC client passive Garden observer, pending continuous-follow on Windows validation.

No user APK, Nox62033, rollback Nox62030, GitHub production/main, Render, Neon or official game servers were changed in Phase3.4E.