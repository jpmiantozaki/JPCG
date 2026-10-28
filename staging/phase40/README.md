# Phase 40: Authorized upstream boundary (staging only)

This folder is intentionally isolated from `public/session.php`, `public/authenticate.php`,
`public/verify.php`, the Cloudflare gateway, and any Render deployment configuration.

## Goal

Separate CGM's membership decision from the official game's authentication/session
authorization. CGM ACTIVE **does not** authorize an official game session. Identity
must be established by an actual official provider, not a claimed login identifier.

## Current behavior

- Missing/expired membership: `MEMBERSHIP_INACTIVE`.
- Membership valid but no verified official identity: `OFFICIAL_IDENTITY_REQUIRED`.
- No official provider: `PROVIDER_UNAVAILABLE`.
- Even an injected provider claiming success: `PROVIDER_NOT_INTEGRATED` (deliberately blocked).
- Every decision returns `authorized=false`, `session=null`, `expires_at=0`.

The implementation **does not accept real PlayPark credentials**, create game-session
packets, replay tokens, or contact the ANLGarden/PlayPark servers.

## Run local test

```sh
php staging/phase40/test_provider.php
```

The seven tests are offline and require PHP 8.1+. The test fixture uses a fixed
clock and dummy identities. No production environment variables are needed.

## What must be established before an official provider can be implemented

1. Permitted interface and account identity/subject binding.
2. Authentication evidence and server-side verification of official identity.
3. Provider-issued session expiry, revocation and replay protections.
4. Provider security requirements and allowed access to Garden functions.
5. Approved integration tests with non-sensitive test accounts.

Do **not** wire this module to production routes or promote to an authorizing
implementation without those requirements and explicit review.


## Phase 40D-E: state machine and real membership lookup boundary

`SessionState.php` tracks local transitions without creating a game session.
`MembershipAdapter.php` reuses `public/_cgm_membership.php` and its
`cgm_membership_lookup(PDO, login, now)` database query via a staging-only
PDO wrapper. This is the **existing CGM codes table**, not PlayPark identity
verification. The adapter trims the claimed key, preserves case, refuses
implicit `INPP.` alias fallback, rejects invalid input, checks the
`CGM_MEMBERSHIP_ONLY` scope and fails closed on lookup exceptions.

The reusable callable form permits offline testing. Tests use stand-in
membership lookups, not the live CGM database. **There is no connected
identity handoff yet**, and a claimed login/email still cannot prove ownership
of the corresponding PlayPark account.

Run these additional offline checks:

```sh
php staging/phase40/test_state.php
php staging/phase40/test_membership_adapter.php
```

No public HTTP endpoint calls this adapter, and it cannot authorize
a Garden session. Before any real identity binding, JPCG will need a
server-verifiable identity result from an authorized PlayPark interface
and an explicit policy mapping that result to the CGM redeemed identifier.

## Phase 40F: exact official-identity binding boundary

`IdentityBinding.php` defines an official identity verifier interface which
is **unavailable by default**. A positive result from a *trusted* verifier is
required to supply both a canonical account login and a stable account subject.
The login must byte-match the *same exact* identifier used for the CGM codes
lookup. No case folding or automatic `INPP.` alias conversion is allowed.

`MembershipAdapter.php` now records the exact lookup login in the local
staging state. This prevents membership lookup for account A and subsequent
binding of a verified identity for unrelated account B.

Offline tests use a fixture verifier **only in the test file**. They do not
use official credentials, contact PlayPark, or prove that an official verifier
exists. An APK-supplied email, `verified=true` flag, client-side success, or
decrypted packet must not be treated as trusted server-verified identity.
Do not pass PlayPark passwords to the `opaqueEvidence` parameter; a future
verifier needs an approved, server-verifiable authentication assertion.

Run:

```sh
php staging/phase40/test_identity_binding.php
```

Even matching fixture identities do not create authorized Garden sessions.
No upstream session provider is integrated.

The legacy `SessionState::confirmIdentity(array)` remains only for older
offline tests. It must NEVER be wired directly to public client input.

Production `public/` routes, Render, Cloudflare and Nox remain unchanged.


## Phase 40G: membership provenance and trust gap

See [PHASE40G_HANDOFF.md](PHASE40G_HANDOFF.md) for the recovered PC/mobile evidence, the exact `authfirst.php` and `verify.php` trust boundaries, and the remaining requirement for server-verifiable official identity evidence.

`MembershipProvenance.php` records whether an ACTIVE CGM membership was found on the requested login ID or its `INPP.` prefix fallback, without equating those keys to PlayPark accounts. The staging precheck carries the *actual* selected codes-table key to the existing identity-binding boundary. Production `public/_authfirst_resolve.php` remains unchanged.

Run the offline suite:

```sh
php staging/phase40/test_membership_provenance.php
```

No official login or independent upstream integration is claimed by these tests.


## Phase 40H — Auth-First architecture correction and APK binary evidence

See [PHASE40H_AUTH_FIRST_ARCHITECTURE.md](PHASE40H_AUTH_FIRST_ARCHITECTURE.md) and [PHASE40H_APK_BINARY_AUDIT.md](PHASE40H_APK_BINARY_AUDIT.md).

Membership precheck is not official account authentication. The existing `authfirst.php` may determine eligibility to *attempt* the original login without JPCG verifying PlayPark credentials. The official LoginServer/GameServer/GardenServer must still authorize the account and session. These are distinct from the unresolved independent HTTP session relay.

An offline scan of the experimental APK found localhost and legacy session/verify literals in Unity metadata but no exact `authfirst.php` or earlier named gate-class strings. This does **not** prove a gate is absent; it means we still lack an end-to-end demonstration that the actual APK enforces membership at credential submission.
