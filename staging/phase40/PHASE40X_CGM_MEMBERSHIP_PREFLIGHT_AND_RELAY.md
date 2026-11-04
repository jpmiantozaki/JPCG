# Phase 40X — CGM-membership preflight + unmodified original login relay

**Status:** Design/acceptance checkpoint only. No APK changes, no upstream changes, no production deployments or credential tests in this commit.

## User's proposed next milestone

Make the existing CGM APK's credential-CONFIRM path check the **CGM codes/membership database** first. For an eligible account, retain the original official login code, relay/session packet and error handling. Use the app's transition toward **Connecting to Game Server** as a visible *progress signal*, not conclusive proof of successful credential authentication, Garden authorization, or a real validated session.

## Already implemented server facts (inspected on branch)

- `public/authfirst.php`: POST JSON `{"schema":"cgm-email-membership-v1","email":"...", "request_id":"..."}` and a legacy login_id contract; returns only CGM membership with ACTIVE/INACTIVE/ERROR, server timestamps/expiry, TTL and correlation ID. The legacy response explicitly reports `game_authorized=false`. Do **not** change production membership semantics.
- `public/_cgm_membership.php`: redeemed `codes` rows indexed by `login_id`; membership ACTIVE only if max vip_expiry exceeds current time. This is a *claimed* identifier, not a verified owner/game identity.
- `public/_authfirst_resolve.php`: looks for plain email and legacy `INPP.` prefix; current `verify.php` defaults to exact identifier unless optional staging alias setting. Conflicting identity representations must be resolved without granting a membership from an untrusted alternate identifier.
- `public/session.php` delegates to `_cgm_session_bridge.php`, which relays original protected request to the ANLGarden-designated upstream *unchanged* and preserves its response, including VIP rejection. It is not a PlayPark official authentication provider.

## Existing binary client chain, with evidence categories

1. **Yellow outer LOGIN** `ConnectToServer(false)` initializes server configuration and displays credential fields.
2. **Inner credential CONFIRM** `Login()` → `SendLogin()` constructs original login packet (contains credentials); `SendRelayInfo()` converts to stage-0 protected relay request, sent to `session.php`.
3. **Existing third-party relay response** `RelayServerData` response callback: `error=true`, `message=VIP` disconnects and calls `ReceiveNoVIPData` (Redeem UI), while `error=false` with valid `data` forwards returned packet to original Login/Game session. User controlled before/after redemption test: same email + same intentionally wrong password yields Redeem UI when no VIP, and official invalid-credentials error after VIP.
4. **Official game and Garden** remain separately authorized; a transition/popup is not a signed security assertion. Distinguish *client-side forward* from any unseen server-side behavior of the third-party upstream relay.

## Proposed design — only if APK pre-submit integration is demonstrated

```text
Inner CONFIRM
  -> read original identifier only (never password)
  -> POST authenticated/TLS membership preflight to authfirst.php
  -> response correlates to same attempt, same identifier and freshness
     ACTIVE, not expired -> invoke original Login() exactly once
                              -> unchanged SendLogin and stage-0 packet
                              -> unchanged existing session.php bridge
                              -> original relay may independently return VIP rejection
                              -> original official login and Garden authorize separately
     INACTIVE/ERROR/timeout/mismatch -> block original submission; correction/retry
```

Do not claim that the current APK already calls `authfirst.php` from the second CONFIRM. No proven suppression of the original serialized Unity callback exists. The backend cannot infer the email-only membership input from the encrypted stage-0 envelope without additional client integration; raw stage-0 packet can contain passwords and should not be decrypted or logged for membership lookup.

## Minimal next evidence / no-repeat acceptance checkpoints

- **Client boundary**: prove a supported way to defer **every in-scope** credential submission until the CGM email-only preflight completes, without replaying original callback twice. Current Houdini Frida on Nox x86_64 sees a guest ARM64 read-only mapping only; synthetic branch fixture is *not* equivalent to production-safe hook.
- **Identity binding**: confirm the normalizing/prefix mapping of the UI identifier to the existing redeemed codes record without leaking identifiers. Do not treat an attacker-supplied login string or legacy prefix as authenticated ownership.
- **CGM membership**: valid unexpired ACTIVE / inactive / expired / error / timeout behavior; correlation by request ID; no password in CGM service requests.
- **Original relay**: permit only original upstream decision. If ANLGarden third-party stage-0 still requires ANLGarden VIP, CGM ACTIVE alone will not reach PlayPark validation. Do **not** rewrite an ANLGarden VIP rejection into `error=false` or synthesize `data` / `session`. Such a response would not establish a genuine server session anyway.
- **Runtime success metrics**: (a) preflight completed and matched, (b) original stage-0 accepted, (c) *official* wrong-credentials reported where appropriate, (d) game-server connection/session legitimate, (e) Garden accepted. The UI popup is a diagnostic checkpoint, not the only acceptance test.
- **Safeguards**: test only on disposable Nox `127.0.0.1:62032`; retain previous `62030` rollback and `62031` untouched, production server configs/DB read-only and no session/packet logging. No repeated live wrong-password tests needed.

## Fundamental separation

**CGM ACTIVE** is eligibility in the user's CGM database; **ANLGarden VIP** is an entitlement controlled by a third-party relay; **PlayPark username/password validity and Game/Garden sessions** are decided by official infrastructure. A client-side check cannot itself confer the other entitlements. Staging tests should show these gates separately instead of conflating them.

## Reuse established upstream, don't rebuild it (archival review, 2026-10-21)

Inspected earlier user Library artifacts `CGM_Owned_Upstream_v1.zip`, `CGM-Owned-Upstream-V1-AuthFirst-Compat.zip`, and `CGM_Provider_Integration_v2.zip`. These packages **already exist** and cover membership checks, as distinct from the real game/session provider. Historical Phase2.6I Stage0 PHP router had:
- `/verify.php?data=<protected account ID>` forwarded an encrypted *identifier only* to CGM-owned `https://jpcg.onrender.com/verify.php`. Prior runtime testing found 2 `INPP.`-prefixed CGM membership IDs ACTIVE and a plain-email version INACTIVE (be careful about identifier normalization and canonical identity). It did **not** prove native APK callback interoperability.
- `/authenticate.php` returned 503 and `/session.php` a protected **failure** because this service wasn't a legitimate PlayPark login/session provider. This is by design and **must not be changed to a fabricated success**.
- Historical local listener `D:\CGMTEST\CGM_Stage0_Test\public\index.php` on loopback `127.0.0.1:8091`. Earlier files are archival; do not overwrite or activate their routes.
- The other `AuthFirst-Compat` Python archive holds a **separate SQLite membership database**, not automatically populated from the current Render/PostgreSQL `codes` table. Do not deploy it as a drop-in replacement or migrate users without an explicit backfill/approval.
- Phase2.6J provider scaffold didn't integrate a real provider; it explicitly disabled authenticate/session success.

**Therefore no more new independent upstream v1 or duplicate membership endpoint is needed**. Work remaining: a trusted pre-submit trigger/deferral in the **existing CGM APK** for the *already working* `authfirst.php` email-only check, with unchanged official/third-party stage-0 session relay after ACTIVE. Normal Unity `Login()` sets `isLoginProcess` shortly after `SendLogin()`; any asynchronous deferral needs to preserve that state and avoid duplicate/re-entrant submissions. Exact installed current CGM target APK is SHA-256 `44b89aa390734b2d05705a20620d6175e298ed790d88734d245e87d98ac00944` (different from an earlier Library signed APK `DEB51B...` despite matching ARM64 lib), so **no claim of patching installed APK** without this exact artifact.

The user goal is to first *observe* CGM membership check before stage-0 and then original login progressing. This can be developed as a client preflight only; it does not authorize ignoring upstream `VIP` rejection or generating session packet bytes. The Game Server popup is a useful UI checkpoint but not proof of valid official/Garden session.
