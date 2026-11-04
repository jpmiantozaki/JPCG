# Phase 40AA - CGM staging upstream routing checkpoint

**Status:** Offline/synthetic integration only. Not deployed. No Nox/PlayPark/ANLGarden interaction or real account authentication.

## What was built

`phase40aa_synthetic_router.php` wraps the Phase 40Z stage-0 parser and an injected local membership lookup plus downstream fixture. The router is **disabled by default** and permits only the reserved synthetic identity `INPP.synthetic@example.invalid` when `CGM_PHASE40AA_SYNTHETIC_ONLY=1` is explicitly set in test code.

For an unexpired CGM ACTIVE result, the exact protected stage-0 request is passed to the injected downstream callback without rebuilding or altering the binary packet, username, password, relay session, or response. The mock provider returns an opaque HTTP status, headers and body. The router returns these unchanged even when the mock provider reports `VIP` rejection. Every other result is blocked before the downstream call. Invalid input and provider failures are fail closed.

**Never use this router on real credential-bearing packets.** It decodes a protected packet in process memory to inspect the *claimed* email; that packet can contain a password. The compatibility packet format is not authenticated identity verification or true end-to-end encryption. Production membership checks should preferably use an email-only pre-submit call bound to a verified identity. No real provider-specific VIP check may be bypassed.

## Observed test results

- Phase 40Z baseline: **15/15 PASS** (`phase40z_test.php`).
- Phase 40AA new: **23/23 PASS** (`phase40aa_test.php`).
- Cumulative offline fixtures: **38/38 PASS**.
- PHP 8.4 syntax checked. No HTTP server, emulator, production API, provider token, password, or Garden session involved.

Among the Phase40AA assertions: exactly-once downstream invocation, byte-identical original protected request, unchanged upstream logical `VIP` rejection, inactive/expired/error fail-closed and no downstream call, wrong-stage/malformed rejection, real/non-fixture identifier rejected, simulated downstream failure fail-closed, and default-disabled guard.

## Run locally

At the root of the extracted archive:

```sh
php -l staging/phase40/phase40aa_synthetic_router.php
php staging/phase40/phase40z_test.php
php staging/phase40/phase40aa_test.php
```

`public/_secure_packet.php` is the existing JPCG compatibility-protection utility included solely for synthetic fixtures. The test archive is not a deployable server.

## Remaining integration boundaries

1. **CGM membership eligibility:** a stage-0 request can carry a *claimed* `INPP.` login identifier, and our already-present membership database can make a decision; this cannot prove the user owns that account.
2. **Downstream entitlement:** the existing third-party ANLGarden relay may return `VIP`, regardless of CGM ACTIVE. An independent replacement cannot manufacture `error=false`, packet data, session keys or game account state. This Phase40AA router does not remove that requirement.
3. **Official login/game authorization:** only genuine provider responses can drive `LoginServerManager.OnPacketReceived()` and GameServer/GardenServer. The mock provider's response is not a valid game login.
4. **Client APK connection:** the installed CGM APK has not been changed. Neither `public/session.php` nor `authfirst.php` has been changed. Before live use, prefer a secure email-only APK preflight that does not route credentials through new test code, or a permitted integration with a legitimate session backend.
5. **Stage 1:** not implemented in this synthetic stage-0 test. A real relay must maintain authenticated, correctly scoped session state and preserve original provider behavior.

**Next decision:** prioritize an owner-approved email-only client preflight adapter or a legitimate downstream session provider interface. Do not route real credentials to synthetic packet-decoding code, or test live VIP bypasses.
