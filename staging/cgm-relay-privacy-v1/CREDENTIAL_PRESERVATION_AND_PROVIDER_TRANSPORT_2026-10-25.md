# CGM 1026 — credential-preserving relay, narrow header policy, and authorized-provider boundary

**Checkpoint:** 2026-10-25. **Scope:** Source review of JPCG's existing public relay plus a **staging-only** PHP header selection utility. No PlayPark/ANLGarden login requests, no APK/Nox operations and no production modifications.

## Clarification: the official credential-bearing packet must not be discarded

The original Unity 1026 Stage 0 login packet carries the case-preserved provider-prefixed account ID, password, and client version. Authentic LoginServer verification must receive the material its real protocol requires. Removing or replacing those fields, or substituting CGM ACTIVE for a game login response, is not acceptable.

Existing JPCG **public/_cgm_session_bridge.php** takes the raw `$_SERVER['QUERY_STRING']` and builds the upstream target with that exact query string. For POST it reads the original `php://input` body. **The forwarding code does not remove, reinterpret, rewrite, or reencrypt the protected `data` payload**. The separate `public/_cgm_session_diagnostic.php` is a read-only metadata inspector; its decrypted view is not used to change the forwarded request.

**Critical endpoint distinction:** This bridge forwards the packet to the configured intermediary `CGM_ORIGINAL_ORIGIN` (default `https://anlgarden.com`), **not directly to PlayPark's official LoginServer**. The original intermediary may perform proprietary packet preparation and maintain its own session. Neither this bridge nor the existing offline zero-transform prototype implements that function independently. A preserved original login packet is necessary in this architecture, but preservation alone is NOT evidence of valid game-bound transformation or PlayPark acceptance.

The original SecurePacket wrapper is reversible XOR/checksum protection; it is not independent cryptographic authentication or sufficient confidentiality. Protect the credential-bearing query over TLS and prevent URL/query retention in ingress, Apache, APM and upstream logs. The previous `staging/cgm-relay-privacy-v1/Dockerfile.staging` handles a portion of Apache access-log privacy only, not the entire chain.

## Specific finding: unrelated HTTP header forwarding

At the time of review, `public/_cgm_session_bridge.php` forwards inbound `HTTP_COOKIE` and `HTTP_AUTHORIZATION` values (as well as User-Agent, Accept and Content-Type) to its configured intermediary. This is **distinct** from the official username/password serialized inside the protected Unity Stage 0 packet. The archived request and native client findings do **not** prove an HTTP Cookie or Authorization header is necessary for the normal BASE yellow-login / CONFIRM flow.

Removing those headers from production without consulting an authorized provider might change behavior. Instead, the staging-only utility is an explicit opt-in for later provider compatibility validation:

- `src/RelayForwardHeaderPolicy.php`: chooses fixed `Accept-Encoding: identity` and `Connection: close`, plus safely bounded ASCII `User-Agent` and `Accept` when available; includes `Content-Type` only for POST; excludes Cookie, Authorization, Referer, Host, forwarded-IP and unlisted client headers. Rejects CRLF/control characters and excess length.
- Does **not** consume, decode, alter or return a credential-bearing protected packet. It performs **no network calls**; its metadata returns provider permission, official identity and game session flags all false.
- `tests/relay_forward_header_policy.php`: **29/29 PASS locally in PHP 8.4** with fictional header values. The source and test have passed `php -l` checks. Existing tests and Nox have **not** been rerun.

### Exact compatibility decision

**Staging only. Do not wire the new header policy into the production bridge automatically.** First obtain a legitimate provider's supported inbound HTTP header contract. In particular, a provider could require separate transport-level authentication or cookies, and any such information must be provisioned securely as **provider-specific credentials** rather than indiscriminately forwarding a user's unrelated ambient headers.

The test does NOT prove successful PlayPark login or independent CGM-only Garden access.

## Next genuine integration dependency

1. Confirm a permitted original/compatible intermediary processor or official supported integration route, with authority to transmit the user's login material to the designated service.
2. Establish its required HTTP header/authentication contract independently of the Unity packet fields.
3. Verify authenticated game identity, provider-issued relay-session continuity, and correct transformed game-bound packets through that authorized provider. Handle all six known Stage 1 commands; keep incoming-client decoder vs outgoing server preparation separate.
4. Obtain real official LoginServer, GameServer and GardenServer acceptance from a permitted test environment. Neither a CGM ACTIVE membership response nor an HTTP `error=false` is sufficient.

The official Audition Next Gen game policy addresses modified clients and third-party software: https://auditionnextgen.playpark.com/game-policy/. This checkpoint should **not** be read as permission to deploy an unofficial alternative client or bypass another provider's entitlement checks.

**Branch-only:** `feat/phase40-staging-provider-interface`. No changes to repository `main`, production Dockerfile, public/session.php, public/_cgm_session_bridge.php, original APK, Nox or live game servers.
