# CGM Auth-First — integration feasibility go/no-go
**Decision date:** 2026-10-23. **Evidence assessed:** historical Phase3.1M/3.3L/2.6W findings, current staging GitHub `public/authfirst.php` and `public/_cgm_session_bridge.php`, actual user Phase40BE–40BO disposable Nox62033 runtime output.

## DECISION
**GO (limited):** design and stage CGM ACTIVE pre-submit client feature and membership-only behavior only after a supported, authorized implementation and identity-binding design is agreed. Original login continuation, independent provider decisions, and safe account switching must remain intact.

**HOLD / NO-GO (current complete product):** do not represent or release a CGM-only Garden APK, or assert BASE/MOD membership is no longer required, without an authorized upstream service that explicitly accepts the CGM integration and provides genuine account-bound login, game and Garden sessions. The current code has not demonstrated such a provider.

No requirement to perform another diagnostic APK build, Nox test, localhost synthetic login run, or JNI metadata proof as a next step.

## Evidence: confirmed vs not confirmed
- Current `public/authfirst.php` is a CGM membership-only endpoint. Its plain response includes `game_authorized=false` even when `state=ACTIVE`; it has no provider-issued game session. Its strict request variant supplies membership `status`, `request_id`, and expiry/TTL, **not** provider authentication.
- Current `public/_cgm_session_bridge.php` forwards `authenticate.php` / `session.php` to `CGM_ORIGINAL_ORIGIN` (default `https://anlgarden.com`) and preserves upstream decisions. It is not an independent issuer. Request forwarding may include credential-bearing packets; no unchecked new relay is approved. Current repo source does NOT independently verify live production environment values.
- Phase3.1M and Phase3.3L studies found no documented authorized independent provider session interface. This means **not supplied or established in project evidence**, not proof a private/partner arrangement cannot exist.
- Existing history: BASE VIP + CGM ACTIVE succeeded in prior combined conditions; MOD VIP + CGM ACTIVE was rejected at BASE intermediary stage, consistent with distinct entitlements. These historic observations are not new live authorization proof.
- Phase40BE original compiled client contacted first-stage `/authenticate.php` once with intentional 503. Phase40BF displayed original credential UI via synthetic loopback config and no-data TCP and observed a non-null `LoginServerManager.Instance`. Phase40BK Java `queueGLThreadEvent` marker executed on the original `UnityPlayer.m_MainThread`. Phase40BL/BN JNI lookup from this thread PASS status210. Phase40BO Java->JNI->IL2CPP harmless `System.String.IsNullOrEmpty(null)` invocation PASS status220. **None performed original Login(), CONFIRM interception, official credentials, PlayPark authenticated session, channel or Garden authorization.**
- Original persistent inner CONFIRM path to `LoginServerManager.Login()` is mapped, but no safe pre-submit replacement, account-linked CGM eligibility check, once-only continuation, or complete alternate-route coverage has been integrated.

## Blocking dependencies ranked
1. **P0 authorized provider and independent session path**: written operator permission and an actual supported integration path under which independent CGM eligibility can coexist with genuine game and Garden authorization without mandatory BASE/MOD VIP. Specify session/authentication interface, authorized stages, status/expiry/revocation, test environment and permission to integrate modified clients. Without this, no meaningful end-to-end release.
2. **P0 authenticated identity binding**: provider-verified stable account identity bound to CGM membership; the login/email entered on the client and prefixes like `INPP.` are not proof of identity.
3. **P1 original login continuation and secure transport**: approved integration point before original CONFIRM submission, complete alternate-route/reconnect coverage, fail-closed async gate, exactly-once original login continuation. Avoid CGM membership service receiving official passwords or session secrets.
4. **P1 full provider-authorized acceptance**: genuine credentials accepted -> CCU -> game -> user info -> Garden on an authorized account, with denial/expiry/reconnect/account switch also validated; provider owns all decisions.

## Decision gates / stop criteria
**Gate A (operator):** identify which entity owns/authenticates the mobile relay, login and Garden entitlements and obtain written approval plus sandbox contract. If none, STOP full-product work; remain membership-only. Contact the service owner or developer; do not infer rights from working original APK.
**Gate B (provider):** demonstrate provider-accepted session initiation/continuation for a valid CGM member in a legitimate test arrangement **independent of BASE/MOD VIP**. If provider contract retains BASE VIP mandatory, CGM-only independence is not achieved and project must be re-scoped.
**Gate C (client):** implement supported and auditable pre-submit mechanism, authenticated identity proof, no credential leakage, cancel/retry/error handling, once-only original continuation, and original session acceptance. Do not write a release readiness claim from isolated JNI tests.
**Gate D (acceptance):** independent active CGM test account without BASE/MOD VIP reaches genuine Garden with upstream consent; inactive/expired are blocked by CGM; rejected/invalid provider account is rejected; reconnect and stale request tests pass. No local synthetic session payload accepted as proof.

## Shortest next action
**Not a new APK test.** Secure the operator-supported session contract and sandbox, or explicit confirmation the provider cannot authorize CGM-only membership. In parallel, produce a concise source-level CONFIRM integration design only, subject to operator permission; no production mutation or real credential capture.

## Source index
- Project Library: `Phase31M_Feasibility_Decision.md`, `PHASE33L_FINDINGS.md`, `Provider-Integration-Contract.md`, `Original-CGM-APK-Compatibility-Matrix.md`, `CGM_Phase26N_Complete_Chat_and_Technical_Backup_2026-10-20.txt`.
- GitHub staging branch: `public/authfirst.php`, `public/_cgm_session_bridge.php`, `staging/phase40/PHASE40BO_MANAGED_PURECALL_RUNTIME_PROOF.md`.
- This memo is a design and evidence decision only, not a test run. No APK/emulator/server/production configuration was changed.
