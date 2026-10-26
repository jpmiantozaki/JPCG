# Phase 40BZ — CGM verified account linking and relay privacy readiness

**Date:** 2026-10-26  
**Status:** Offline PHP policy/source package validated. **NOT integrated into `public/`, not built/deployed on Render/Cloudflare, not installed in any Nox APK.**

## Motivation from Phase 40BY

- The current `public/authfirst.php` returns **CGM membership status based on a client-claimed email**. It does not prove ownership of the corresponding PlayPark account. `_authfirst_resolve.php` may check both the plain email and the `INPP.`-prefixed database key, which is an accounting compatibility lookup, not provider-issued identity proof.
- `public/session.php` forwards the original request to `CGM_ORIGINAL_ORIGIN` (default an external third-party intermediary); it is **not** an independent authorized GameServer/Garden session issuer. Its transport may carry reversible protected credentials in `GET ?data=`.
- The ordinary production `Dockerfile` does not select the previous staging-only path-only Apache access-log configuration. JPCG's own PHP metadata logs cannot guarantee no ingress/proxy/APM/upstream query retention. The bridge also forwards incoming Cookie and Authorization headers to the configured upstream.
- Phase 40BX's provider admission adapter remains unconfigured and denies without an authorized official verifier.

## New offline source checkpoint

Artifact produced: `CGM_Phase40BZ_Identity_Privacy_Staging_OFFLINE.zip`.  
SHA-256: `a5dbee2ea57b30f63adde2df6d29a8e64e5afbe1c7e8029d46fe24a8ec617e85`.  
The ZIP contains PHP source, tests, README, validation evidence and a SHA manifest; no original APK, proprietary game packet, passwords or tokens.

### `src/CgmAccountOwnershipGate.php`

- `UnconfiguredOfficialAccountVerifier` **always denies**; a synthetic server-side trusted verifier is used in host tests only.
- Separates a claimed email from the **official-provider-verified principal** (issuer, stable subject, verified email, unexpired provider session), **server-stored exact account link**, and server-owned CGM membership lookup.
- Requires exact trusted link identity and expiry, nonrevoked status, exact server-side membership lookup key, and CGM ACTIVE/unexpired membership. Does **not** infer a trusted mapping by merely prepending/removing `INPP.` or selecting a longer expiry across claimed aliases. An explicit link may use a plain email or its exact `INPP.` form.
- Returns `scope=CGM_OWNERSHIP_LINK_POLICY_ONLY`, `session_issued=false` and `game_authorized=false` even on a positive **synthetic** policy decision. Does not generate login or relay packets, credentials or Garden sessions.
- This policy is **not connected** to a real official verifier, to a provisioned link database, or to the existing `authfirst.php` APK contract.

### `src/RelayPrivacyReview.php`

- Pure, nonforwarding inspection. Requires explicit HTTPS origin and independently pinned exact DNS hostname; rejects URL credentials, query, fragment, path prefixes, IP/localhost/local domains, unexpected port and missing configuration.
- Reports only permitted transport **header names**, excludes Cookie, Authorization, Referer and forwarded-IP, rejects control characters and oversized/untrusted header values.
- **Never** claims upstream permission, enabled forwarding or end-to-end log safety: `permission_verified=false`, `forwarding_enabled=false`, `credential_query_logging_safe=false`.
- It does **not** wire into the actual proxy or replace `staging/cgm-relay-privacy-v1`'s earlier separate Apache/header utilities.

## What was really tested

```text
PHP CLI 8.4.24:
  php -l src/CgmAccountOwnershipGate.php        PASS
  php -l src/RelayPrivacyReview.php             PASS
  php -l tests/offline.php                       PASS
  php tests/offline.php                          PHASE40BZ_IDENTITY_AND_RELAY_PRIVACY_OFFLINE_PASS assertions=115

ZIP archive CRC: PASS
SHA256 MANIFEST: PASS for every file after clean extraction
Clean extracted archive re-test: 115/115 PASS
```

The tests use fictional identities and no actual HTTP request or official session proof. The positive policy case is **synthetic**; it does not establish a working provider integration.

## Actionable remaining gates — no more repeated APK tests

1. A **permitted, trusted provider verification contract** that supplies a stable official account subject plus expiry, and an auditable **server-stored** link from that subject to the exact CGM membership key. The existing claimed-email-only membership endpoint cannot supply this proof.
2. A reviewed, permitted upstream routing and transport contract before any genuine credential-bearing URL is forwarded. Verify path-only/no-query logging across ingress, container, reverse proxy, APM, hosting and upstream; protect credentials in transit; remove ambient Cookie/Authorization forwarding only after contract compatibility review.
3. Official LoginServer/GameServer/GardenServer acceptance and an independently authorized compatible mobile session processor remain **unresolved**, distinct from all CGM membership and privacy work.

**Environment:** No changes to repository default branch, `public/`, production deployment, test APK, Nox `127.0.0.1:62033` or rollback `62030`. The known-working Phase 40BP signed APK remains the verified restored baseline. The user's Windows PHP CLI was unavailable; testing was carried out in the authoring environment, so no Windows test commands are required.
