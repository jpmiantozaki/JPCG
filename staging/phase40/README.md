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
