# Phase 3.4F — Cryptographically verified provider interface

**Date: 2026-10-29. Mode: staging-only, offline. No real PlayPark account or game-session requests.**

## Concrete implementation

Implemented a real Ed25519/JWS verifier satisfying the existing `CGM\Phase40CD\AuthorizedProviderVerifier` interface. The verifier is deliberately **VERIFY-ONLY**: it never issues signed tokens or game sessions. It requires the operator's independently trusted Ed25519 public key, exact key ID, issuer and audience. It does not download token-supplied keys or follow arbitrary provider endpoints.

- `src/Ed25519ProviderVerifier.php`: pinned-key Ed25519 signature check, reject `alg=none` and signature/key/header manipulation, strict flat/no-duplicates JSON claims, issuer/audience/email/subject/attempt binding, issued/not-before/expiration validation, literal authenticated game/Garden/intermediary-entitlement claims.
- `src/PinnedProviderVerifierFactory.php`: server-owned env-only key configuration, returning `UnconfiguredProviderVerifier` (always denies) if missing, malformed or partial.
- Exact tested new source is stored as `source-patches/CGM_Phase34F_Runtime_Source.tar.xz.b64`. Decode and extract in staging only. SHA256 of the decoded XZ file: `1721bb79b611adf03f13c71263d4330dddd608d7f7645ca46d0df9a09e128176`. **Existing** `src/ProviderHandoffGate.php` remains unchanged.
- Standalone conversation artifact `CGM_Phase34F_Signed_Provider_Adapter_2026-10-29.zip` includes verifier, factory, existing provider gate reference, readme, tests and SHA manifest. Full ZIP SHA256: `82e03d505ca5dec443ef23327bf70d617fc962bb3c828126518d107f8c59d7b0`.
- PHP8.4 offline tests: **55/55** signed-proof + existing gate tests and **15/15** factory tests PASSED; clean ZIP re-extraction CRC and file SHA manifest PASSED.
- GitHub Actions runs synthetic signed-proof regressions, without network, original APK, PCAP, passwords or issuer private keys.

## Required externally signed JWS contract (not a documented PlayPark API)

Header exact keys: `alg=EdDSA`, `typ=JWT`, `kid=<pinned operator key ID>`.

Claims exact keys: `iss`, `sub`, `email`, `aud`, `attempt_id`, `iat`, `nbf`, `exp`, `game_authenticated`, `garden_authorized`, `intermediary_entitlement_accepted`.

Both `iat` (within 10 seconds) and `exp` (short lifetime <=120 seconds from issue) must pass. All three rights must be **literal boolean true in an independently trusted provider-signed attestation**. The provider cannot be self-appointed by JPCG; a CGM-owned signing key is not proof of PlayPark authorization.

The verified `VerifiedProviderSession` is fed into existing `ProviderHandoffGate`, which additionally requires exact server-owned account linking, fresh ACTIVE CGM membership, and a durable global one-use attempt. A positive **synthetic** test verdict remains only `PRECONDITIONS_ATTESTED_ONLY`, `session_issued=false`.

## Important negative finding

Verified public PlayPark user guides explain PlayID registration and ordinary game login but provide no confirmed public Audition Game/Garden session-verification or signing-key API. An ordinary PlayID web login does **not** imply entitlement to the original separate mobile intermediary. This Phase **does not** provide an actual PlayPark signature or validated live session; no service-agnostic crypto code can supply that external authority.

## What is still required for actual playable CGM-only login

1. A **real authorized provider/operator interface**, including trusted key provenance or a documented session-verifier contract.
2. Legitimate official GameServer/GardenServer session issuance or an approved server-side processing path for the same verified account; client response shapes and historical PCAP cannot mint it.
3. Exact bound account link and replay-safe attempt lifecycle from existing Phase3.0/3.4D/3.4E components.
4. End-to-end credential/logging privacy review before any live application integration.

Original third-party `VIP` rejection must remain a denial unless that intermediary itself authorizes the account. This code cannot bypass it or rewrite a failure into a fake success.

**UNCHANGED**: JPCG default branch / live `public/session.php`, Render, original signed APK, Nox62033 and rollback62030, game servers and user accounts.
