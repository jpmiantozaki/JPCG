# Phase 40G — Handoff evidence and membership provenance

## Verified client-side facts

- Official PC game analysis identified `PlayParkCEFLogin.dll` browser-based login
  and a local callback which supplied fields to the PC client's TCP login path.
  The callback and packet transport alone are NOT a server-verifiable identity
  assertion available to JPCG.
- APK `RelayWebManager.RelayServerData` forwards protected requests to an
  intermediary HTTP upstream; `MGRelayResponse` can carry binary LoginServer
  and GameServer data. This does not establish independent session issuance.
- The APK's LoginServer success path populates state used to connect to the
  GameServer, but client-local values and events are not identity evidence.

## Verified JPCG source contracts

| File | Existing behavior | Identity strength |
|---|---|---|
| `public/authfirst.php` | CGM membership query for claimed email/login_id | None |
| `public/_authfirst_resolve.php` | Looks up plain email and optional `INPP.`-prefixed key; chooses highest active expiry | None |
| `public/verify.php` | Decrypts client-supplied login identifier; legacy checksum is not an identity signature | None |
| `public/authenticate.php` | Calls forwarding bridge | No independent official identity assertion |
| `public/session.php` | Calls forwarding bridge | No independent official identity assertion |
| `public/_cgm_session_bridge.php` | Forwards session requests/responses to configured ANLGarden origin | No JPCG-issued authorization |

## Concrete finding: membership key selection is not recorded

`authfirst_resolve` returns only `state` and `vip_expiry` even when the
membership actually came from a different key (`INPP.email` instead of
`email`). This matters for any future attempt to bind the membership to a
PlayPark-verified account; two strings cannot be treated as one principal
without an explicit authorized mapping policy.

## Phase 40G staging implementation

`MembershipProvenance.php` reproduces existing resolution ordering but
retains the selected database key and whether it matched via `EXACT` or
`PREFIX_FALLBACK`. The stage only accepts trusted lookup payloads and
fails closed for inconsistent records, invalid inputs, or lookup errors.

The precheck records the chosen database key in `CgmStagingSessionState`.
Future trustworthy identity evidence must be independently verified and
must map to the same account under an approved account-binding policy.

**This code does not verify official credentials, prove account ownership,
connect to PlayPark, create a relay packet, or grant Garden access.**

## What would close the remaining evidence gap

1. An official trusted identity assertion, such as a verifier-controlled
   callback/server validation response with a canonical account identifier.
2. A documented trust mechanism: verifier authority, audience, expiry,
   replay protection, and exact mapping to the CGM membership database key.
3. A legitimate permitted session provider for GameServer/Garden flows.

A local browser callback or mobile UI success alone cannot satisfy (1).
No live upstream, APK, EXE, gateway, or emulator changes are included.

## Local test

```sh
php staging/phase40/test_membership_provenance.php
```

Fixture results are only staging checks; no official login is performed.
