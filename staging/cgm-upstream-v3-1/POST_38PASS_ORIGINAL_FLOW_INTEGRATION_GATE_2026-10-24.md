# CGM — Original BASE/MOD flow integration gate after Identity Boundary 38/38 Windows PASS
**Date:** 2026-10-24 | **Decision:** preserve original client contract, keep experimental identity gate isolated, no live/prod change.

## Confirmed user result
User ran `CGM_Identity_Trust_Boundary_v1/tests/run.php` on their Windows PC; all **38/38 PASS**, including signature validation, issuer/audience, strict namespace, replay blocking, precise membership lookup, credential rejection, safe forwarding-header helper, and fail-closed handling. This validates standalone synthetic/local behavior only; it does not demonstrate game identity attestation, a successful 1026 login, or Garden access.

## Evidence from checked JPCG branch source

The Docker build copies `public/` into Apache:
- `public/authenticate.php` calls `cgm_session_bridge('authenticate.php')`. The bridge requests server configuration from the external configured origin; not game authentication.
- `public/session.php` calls `cgm_session_bridge('session.php')`. This sends the original protected request to the configured origin and preserves its response. Default `CGM_ORIGINAL_ORIGIN=https://anlgarden.com`; an origin may still reject CGM-only VIP.
- `public/verify.php` accepts original native SecurePacket-formatted login-ID query and performs **exact** CGM membership lookup by default. `CGM_VERIFY_ALLOW_PREFIX_ALIAS=1` exists but is flagged staging-only, not proven safe for production.
- `public/redeemvip.php` saves the submitted identity verbatim (trimmed, case-preserved) with redeemed CGM code and returns native encrypted MGRelayResponse, HTTP 200 for logical results. It does not establish official game-account ownership.
- `public/analytics.php` forwards to the external origin.
- `public/authfirst.php` is **a separate experimental membership-only route** returning ordinary JSON with `game_authorized=false`. `_authfirst_resolve.php` can search both plain email and `INPP.` prefix. This alias fallback differs from the original-compatible exact `verify.php` path and **must not silently be treated as original client behavior**.
- `bound_membership.php` from the separate, offline v1 identity ZIP is not in deployed public routing; it requires a **trusted external attester** to supply a short-lived game-identity assertion, which the unchanged APK does not produce.

## Concrete protocol and authorization sequencing constraint

From earlier native/archived client mapping: yellow LOGIN fetches configuration and makes a game LoginServer connection; CONFIRM serializes an original game login packet via `LoginServerManager.SendLogin`; `RelayWebManager.SendRelayInfo` sends it to mobile `session.php` as stage 0; upstream can reject `VIP` **before** original game credentials have been verified. On genuine upstream success the client forwards `MGRelayResponse.data` to the configured original LoginServer and later performs stage1 CCU/logon via the existing game connection. Legacy Garden VIP verification is a separate later check.

Therefore, a cryptographically trusted **post-authenticated** account identity is not automatically available at the **pre-authentication** stage-0 VIP gate. This is the core circularity. The 38/38 signed-assertion experiment cannot simply be inserted into `session.php` for the unchanged APK without a separate supported provider identity assertion or changing the client's behavior. `INPP.` string, SecurePacket checksum, CGM ACTIVE DB record and protected original credentials alone do not constitute trusted game-account proof.

## GO / NO-GO decisions

| Decision | Status | Reason |
|---|---|---|
| Keep original five APK-facing endpoint contracts | **GO** | Preserves existing original client behavior |
| Keep `authfirst.php` and identity experiment as out-of-band offline components | **GO** | No legacy APK caller; prevents accidental login changes |
| Continue membership-only exact lookups and CGM-code redemption | **GO** | Existing workflow, separate from official auth |
| Replace stage0 path with signed assertion-only `bound_membership.php` | **NO-GO** | No verified trusted attester at that point |
| Enable plain/INPP alias flag globally | **NO-GO** | Potential cross-identity entitlement and redemption inconsistency |
| Interpret CGM ACTIVE as game-authenticated | **NO-GO** | Not an identity proof |
| Deploy staging v3.1 as independently working game session service | **NO-GO** | Still delegates valid packet preparation to BASE/MOD existing origin |
| Rewrite upstream `VIP` denial to `error=false` | **NO-GO** | Forges response; provides no valid accepted game packet |
| Implement independent session processor from authorized source or supported provider API | **CONDITIONAL GO** | Requires legitimate packet preparation, official server acceptance, and relay state contract |

## Precise integration contract once a compatible provider path exists

1. Keep standard BASE/MOD original client endpoints; avoid APK modifications.
2. For stage0, distinguish **CGM membership eligibility of the claimed login** from **official identity proof**. A claimed-ID check can deny ineligible logins but must never independently grant game authorization.
3. Pass the original packet to a **legitimate supported session processor** that produces real original-game-compatible packet data and true stage relay state; do not derive from historical +5 byte length or test fixtures.
4. Require original game authentication response and state before treating game identity as authenticated; if authoritative identity attestation is supported, bind CGM membership to that trusted identity. Never treat unverified stage0 username as that identity.
5. Stage1 requires compatible processor and **genuine continuity**; do not equate a mobile relay token with PlayPark server state.
6. Preserve original Garden authorization flow and expiry/redeem behavior.

## What to do next (finite, high-information)

Do not ask user to rerun 38/38, 37/37, 40/40 or archive scans. The one remaining blocker is documented **authorized compatible session processing** and an actual trusted identity binding mechanism available at the appropriate stage. If neither exists, the correct endpoint behavior is a clear fail-closed response; synthetic `error=false` cannot solve it. Staging security/secret-handling work may proceed separately without claims of working CGM-only login.

**No APK/Nox/live production route touched.** This checkpoint records verified code reading and the user's completed test.
