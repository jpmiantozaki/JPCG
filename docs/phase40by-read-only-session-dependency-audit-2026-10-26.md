# CGM Phase 40BY — Read-Only Session Dependency & Authorization Audit

**Date:** 2026-10-26 (Asia/Manila)  
**Scope:** Existing JPCG `feat/phase40-staging-provider-interface` PHP code; prior user-provided October 8 BASE relay observations and October 25 ARM64/client-lifecycle reports; October 26 BP/BV/BX continuation.  
**Mode:** Source and evidence review only. No HTTP probe, login, APK build, Nox action, deployment, production branch change, or packet/token generation.

## Executive finding

**No independent, provider-authorized PlayPark/Garden session issuer or verifier is present in the inspected backend.** CGM membership eligibility is implemented. The currently shipped relay path is a request/response forwarder to a configured external mobile intermediary. The separately prepared Phase 40BX admission library is source-only and defaults to *deny* without a trusted official provider verifier. A successful CGM ACTIVE lookup, a client CONFIRM click, a protected positive relay response, or successful local socket write must not be represented as a game/Garden session.

**Current safe state:** Phase 40BP known-working test APK restored and hash-verified on disposable Nox `127.0.0.1:62033`; rollback Nox `127.0.0.1:62030` untouched. Phase 40BV is a user-attested, isolated deny-only behavioral PASS, not an official service authorization. Phase 40BW was offline source-validated but not installed. Phase 40BX was host-tested with 56 synthetic assertions but not integrated into any real provider.

## 1. Exact backend map (staging branch reviewed)

| Boundary | Current source behavior | Evidence level | What it does **not** prove |
|---|---|---|---|
| `public/authfirst.php` | Validates JSON input; looks up CGM redeemed-code expiry for an email, optionally checks both plain email and `INPP.` alias; returns `cgm-email-membership-v1` status, request ID, expiry and maximum ~5-second client lease | **Code verified**; prior BP live ACTIVE markers | Ownership of claimed official PlayPark account, official authentication, or session eligibility at the mobile intermediary |
| `public/_cgm_membership.php` | Queries `codes` table for redeemed login ID and latest expiry; ACTIVE only if expiry is in future | **Code verified** | Account ownership proof, game-state proof |
| `public/authenticate.php` | Forwards to `_cgm_session_bridge.php` | **Code verified** | Independent game configuration authority |
| `public/session.php` | Forwards to `_cgm_session_bridge.php`; optional diagnostics are metadata-only | **Code verified** | Credential verification, protected-packet transformation, genuine token issuance or VIP authorization |
| `public/_cgm_session_bridge.php` | Forwards original query/body to HTTPS `CGM_ORIGINAL_ORIGIN`, default `https://anlgarden.com`, and transparently relays status/selected headers/body | **Code verified** | Direct connection to official LoginServer, independent intermediary implementation, operator-granted authorization |
| `public/_cgm_session_diagnostic.php` | Classifies outer protected envelope/response shape, stage, packet size and allowlisted errors | **Code verified** | Validation of true game packet format, official session, or packet transformation |
| `public/verify.php`, `public/redeemvip.php` | CGM-only protected membership lookups/redemption | **Code verified** | Official game authentication or session grant |
| Phase 40BX `src/AdmissionGate.php` (not deployed) | Requires independent membership + trusted provider session verdict; `UnconfiguredOfficialProviderVerifier` returns null/deny | **Offline source + 56 host tests** | A working provider verifier, new session issuer, actual session persistence or replay defense |

**Source:** `https://github.com/jpmiantozaki/JPCG/tree/feat/phase40-staging-provider-interface/public`, `docs/phase40bx-authorized-session-admission-contract-2026-10-26.md`; independent inspection of the supplied Phase40BX source package.

## 2. End-to-end protocol and trust boundaries

1. **CGM eligibility:** user provides an email to the CGM backend. Membership ACTIVE is a database-derived property, *not* proof that the caller owns that PlayPark identity. The original sender may use a provider prefix (`INPP.`). The plain-vs-prefixed lookup reconciles database keys but does not by itself establish two account names represent the same authenticated identity.
2. **Original Stage 0 submit:** original `LoginServerManager.Login()` calls `SendLogin()`; original client emits framed login command `[01,01]` with credential fields and a counter; `RelayWebManager.SendRelayInfo` wraps `packetHex~stage~relaySession` into protected `/session.php?data=...`. Phase40BS showed one synthetic endpoint hit after CGM ACTIVE and fictional password. Phase40BV later showed one user-attested CONFIRM click with `session=0` under an unconditional native deny-only diagnostic; this is evidence about local submission behavior, not real provider behavior.
3. **Mobile relay backend:** JPCG forwards the protected envelope unchanged to the configured intermediary. The historic BASE upstream can produce an application-level response holding `error`, `message`, `data`, `servertype`, `session`, `vip_expiry`. An original intermediary VIP response (`error=true`, `message=VIP`) may stop this process separately from CGM membership. **Do not silently convert that rejection into success.**
4. **Official LoginServer:** when the relay application response is positive, the client forwards returned packet bytes to the current local LoginServer TCP session. A local `Session.Send` / `NetworkStream.Write` is transport only. A **real LoginServer reply** parsed by `LoginServerManager.OnPacketReceived` is required before proceeding to actual game service state.
5. **GameServer:** original app proceeds through VerifyCCU, Game Logon, UserInfo, EnterChannel and other real inbound server-response-dependent transitions. A synthetic status, HTTP 200, well-formed JSON, nonempty `data` or a 12-byte relay-session **length** cannot substitute for these game acknowledgments.
6. **Garden:** GardenServer connects and processes its own login/load result, with some separate official Garden web API/session paths (`appver=1002` versus client app version 1026). No current CGM backend component has been shown to issue a compatible Garden session.

## 3. Historical evidence: what is known and what remains unknown

- Three October 8 **BASE-origin** positive-looking exchange clusters contain 20 Stage0/Stage1 command exchanges in total, *not* 20 independent successful logins. The fresh 13:49 UTC cluster had an empty incoming Stage0 relay token; earlier Stage0s contained a token. Token lengths alone do not show token continuity, issuance, rotation or official account binding.
- For identical request frames in different sessions, outputs had differing lengths (UserInfo 8-byte request -> 17/13/14-byte output, Ping 8-byte request -> 17/15-byte output). Thus no validated stateless, fixed-padding transformation follows from the trace. The exact protected game-bound packet preparation and mobile relay session lifecycle remain unknown.
- Original callback: a positive `MGRelayResponse` stores the response relay string in `RelayWebManager.session`, then sends decoded `data` to **the current** LoginServer or GameServer Session selected at delivery time. It does not prove true server acceptance. A negative parsed application response may disconnect current connections and show the redeem UI.
- Native HTTP lifetime audit: requests can outlive socket disconnect, and callbacks can be queued beyond the active HTTP request list. Captured manager versus current Session may differ; a **stale callback race is source-supported but NOT reproduced at runtime**. Existing packet counters reset across Session instances and their getters increment state, so neither is a global request ID.
- Earlier reference screening found **no validated drop-in independent 1026 `session.php` processor or official third-party verifier**. This is a negative finding from that search and inspected repository, not proof none could ever exist.

## 4. Non-game-session security issues to track in CGM-owned infrastructure

### Finding S1 — Account ownership and alias policy

`authfirst.php` accepts a supplied email; even its strict schema and request ID are not PlayPark authentication. `authfirst_resolve()` may select an ACTIVE membership from plain or `INPP.`-prefixed stored keys. The separate redemption endpoint binds codes to a text login ID. Consequently, **membership status must not act as identity proof** for a separate game account. The Phase40BX provider verifier correctly fails closed by default but a future provider mapping must establish a trusted stable account ID, alias conflict handling and same-identity binding. No active session grant should derive merely from possession of a known email.

### Finding S2 — Credential-bearing GET logging / proxy trust

The original Stage0 protocol embeds credentials inside a reversible XOR/checksum outer wrapper; `session.php?data=` may therefore carry sensitive material. JPCG's PHP metadata logger omits raw query strings, but this **does not override Apache, ingress, upstream or APM access logging**. Earlier analysis verified the normal Dockerfile uses `php:8.3-apache`, `COPY public/`, and does **not** opt into the staging-only `staging/cgm-relay-privacy-v1/Dockerfile.staging` access-log mitigation. Historic owned logs did contain full query strings. **Deployment logging still requires an explicit audit before any genuine credentials pass through that relay.**

### Finding S3 — Extra forwarded HTTP credentials

The current relay forwards inbound `Cookie` and `Authorization` headers, in addition to the original packet query/body, to the configured upstream. No archived evidence establishes that such HTTP-level credentials are required for the normal yellow LOGIN / CONFIRM game path. A narrower header allowlist exists only as staged investigation; it is not verified active in the present production Dockerfile. Evaluate provider compatibility with allowed synthetic requests rather than removing official packet credentials or silently altering deployed behavior.

### Finding S4 — HTTP callback ownership

The client callback can act on a newer current socket despite being associated with an older submitting manager. That is a **static risk**, not an observed exploit. A future authorized client implementation should associate request generation, expected connection and delegate identity before applying either positive **or negative** effects. Do not generate test credentials or raw packet logs merely to investigate.

## 5. Stop/go decision matrix

| Gate | Evidence needed to say GO | Result as of this audit |
|---|---|---|
| CGM membership eligibility | Valid redeemed ACTIVE with server time, exact claim and expiry | **PASS (eligibility only)**, real BP gate previously observed |
| Original local LOGIN submission/denial | Single click with synthetic credentials and local path count | **PASS** for separate BP allow and BV deny-only controlled fixtures; BW conditional not installed |
| Authenticated official account binding | A provider-validated stable identity matched to CGM membership | **NO GO** — not established |
| Independent authorized mobile relay | Verified provider compatibility/permission, Stage0 and Stage1 packet handling and genuine relay state | **NO GO** — current JPCG is a forwarder |
| GameServer acceptance | Actual genuine server response and state transitions | **NO GO** for any CGM-owned independent upstream |
| Garden authorization | Actual permitted Garden login/load acknowledgments | **NO GO** for an independent CGM path |
| Safe production transport logging | Proven no sensitive query/header persistence end-to-end | **NOT VERIFIED**; staged mitigations not active in normal Dockerfile |

## 6. Finite next engineering checkpoint — without repeating completed APK tests

**Phase 40BZ recommended: CGM-owned identity/privacy readiness, staging only.**

A. Define a *trusted account-identity binding contract*: input from an independently validated official session / supported authorized identity token, canonical account identity, and fail-closed mismatch/alias policy. Test it with synthetic identities only. Do not pretend email knowledge is official account possession.

B. Audit and harden **CGM-owned ingress and logging** by promoting the previously staged path-only logging design into a controlled nonproduction build *after* deployment-path inspection; track potential Apache, ingress, APM, and upstream logging separately. Review header allowlist compatibility only on synthetic traffic. Do not change production without independent approval and a canary plan.

C. Keep `AdmissionGate.php`'s unconfigured official verifier as a deny-by-default stub. Do **not** expose an active Garden/session authorization endpoint or invent provider tokens from CGM ACTIVE. If a legitimate provider session-validation interface cannot be identified in available authorized materials, record this as a hard dependency rather than cycling through more client patch versions.

D. On next review, evaluate only **new evidence** capable of satisfying a NO-GO gate; otherwise stop this branch of integration and preserve the existing tested client/CGM membership tools.

No user PowerShell or PHP installation is required to read this report. No commands to run are requested. Never access rollback Nox62030.

## Provenance, with source classification

**GitHub staging source (read in this audit):**
- `public/authfirst.php`, `public/_authfirst_resolve.php`, `public/_cgm_membership.php`, `public/_common.php`.
- `public/authenticate.php`, `public/session.php`, `public/_cgm_session_bridge.php`, `public/_cgm_session_diagnostic.php`, `public/_secure_packet.php`, `public/verify.php`, `public/redeemvip.php`, `Dockerfile`, `render.yaml`.
- `docs/phase40bp-cgm-active-runtime-recognition-2026-10-25.md`, `docs/phase40bs-original-confirm-one-shot-local-session-2026-10-25.md`, `docs/phase40bv-guest-login-witness-offline-2026-10-26.md`, `docs/phase40bx-authorized-session-admission-contract-2026-10-26.md`.
- Staging privacy notes in GitHub commits `7cb64ba3949bbfaeba6596a1aa166bc049071207` and `68a80642c5e553aa9cd896e2b98f605b5c1f7c04`.

**Prior, not freshly reproduced:**
- `CGM_AuthFirst_Full_Conversation_Findings_Backup_2026-10-25.txt`, 20 historical BASE exchanges across 3 clusters.
- `CGM_1026_Upstream_Reference_Screening_2026-10-24.md` (negative independent processor screening).
- Mounted read-only ARM64 audits: `CGM_RelaySession_Lifecycle_Audit.md`, `CGM_Disconnect_HTTP_Lifetime_Audit_2026-10-25.md`, `CGM_Callback_Ownership_and_Abort_Audit_2026-10-25.md`, `CGM_Request_Identity_and_Counter_Audit_2026-10-25.md`.
- User-reported October 26 Phase40BV current-process markers, one confirmed CONFIRM click and `config=1/session=0/other=0`, followed by exact-hash Phase40BP restoration.

**Do not confuse code inspection with live verification.** This report does not claim the latest Render environment variables, provider availability, or a fresh official server response have been checked.