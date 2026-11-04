# CGM 1026 — session issuance, rotation evidence and authorized processor gate

Date: 2026-10-24. Offline review of the October 8 BASE-origin archives, decompiled original Unity client callback, and the current JPCG backend/staging. No live login, APK, Nox, or production changes.

## Findings: distinguish the three values

For each Stage 0 event the relevant witnesses are: (A) previous mobile relay identifier supplied by client, (B) exact session identifier returned inside Stage 0 MGRelayResponse, and (C) identifier sent on the first subsequent Stage 1 request.

- 13:33:07: A was present; C was present across five Stage 1 requests and differed from A. **B was not recorded.**
- 13:33:32: A was present; C was present across six Stage 1 requests and differed from A. **B was not recorded.**
- 13:49: A was empty; C was present across six Stage 1 requests. **B was not recorded.**

Original native RelayWebManager positive response callback RVA 0x6B088C writes MGRelayResponse.session into the relay context before forwarding returned data to LoginServer or GameServer. This establishes client-side handoff, not the intermediary's authentic session issuance or rotation semantics.

The existing public/_cgm_session_diagnostic.php logs response.session presence and length, not its value or equality with the next request. The historical logs therefore cannot independently prove A-to-B or B-to-C equality, authentic issuance, reuse, revocation or cross-account binding. HTTP requests can overlap and their log arrival order differs from original packet counters; a future witness must distinguish request construction order and response callback completion.

## New offline source and local verification

- src/RelaySessionHandoffEvidence.php — equality-only audit classifier.
- tests/relay_session_handoff_evidence.php — **122/122 PASS** using synthetic strings under PHP 8.4, both files PHP lint PASS.
- Null input means not observed, while empty string means observed absent.
- Recognizes fresh, same-value, changed-value and mismatch handoff relations; deliberately never labels a changed value as authenticated server rotation.
- Does not log or return tokens, hashes, credentials or packet bytes.
- Every outcome returns false for session_issuer_verified, relay_session_authenticated, official_identity_bound, official_login_accepted, game_server_accepted, garden_server_accepted and may_forward.
- Historical Stage 0 response session values are unavailable, so their legitimate rotation classification **remains unresolved**. Synthetic tests do not fill that evidence gap.

## Separate authorized game-bound processor requirements

| Phase | Confirmed client structure | Genuine provider/server requirement not yet established |
|---|---|---|
| Stage 0 Login command 01 01 | Framed login; response routed to original LoginServer | Permitted outbound packet processing, official LoginServer acceptance, account-linked issued relay state |
| Stage 1 VerifyCCU 00 16 | Native CCU reply handler; positive branch leads to Game Logon | Genuine official CCU acceptance, not a locally observed request |
| Stage 1 GameLogon 01 00 | Native logon reply path can lead to UserInfo | Genuine game logon acceptance and server-provided identity |
| Stage 1 UserInfo 0A 00; CoupleLevel 1F 0C; EnterChannel 09 00; Ping 00 00 | Six original Stage 1 client senders mapped in total | Compatible outbound packet transformation for all commands, relay ownership, concurrent in-flight continuity |
| Garden | Channel response-dependent ConnectToGardenServer; Garden login with official userSN, CCU and home-premium state | Genuine official channel/Garden acceptance tied to legitimate authenticated account |

The original native PacketEncryption.Decrypt processes **incoming game-to-client packets**, while the HTTP intermediary's positive data is forwarded as **outgoing client-to-game bytes**. Matching variable length overhead ranges do not establish inverse algorithm compatibility. Identical original eight-byte requests yield different output lengths across distinct relay contexts; packet-only stateless processing does not explain the recorded behavior.

## Architectural constraint

Production public/session.php calls the forwarding bridge to the configured existing upstream; it does not implement the independent authenticated game-bound processor. public/authfirst.php returns membership-only scope with game_authorized=false. The offline ZeroTransformRelayCandidate locally invents a simulated relay token and replays the original untransformed client bytes; its positive result is **not** an authorized game-session outcome and it lacks full observed Stage 1 coverage.

### Required authorized evidence to unblock

1. Provider/operator-documented fresh and re-login behavior for incoming empty vs existing Stage 0 relay identifier; renewal, rotation, expiry and revocation tied to an authenticated official principal.
2. Authorized and privacy-reviewed in-memory equality witness for A, B and C (output comparisons only, no raw identifiers).
3. Explicit concurrent-request/callback ordering and lifetime semantics for shared relay-state updates.
4. Permitted intermediary processor source or supported interface that correctly produces game-bound packets for the original framed commands.
5. Actual original LoginServer/GameServer/GardenServer acceptance evidence under authorized test conditions; HTTP error=false and CGM ACTIVE are not enough.

Decision: **NO-GO** for live independent CGM-only accepted official game/Garden sessions until all necessary authorized processor and official authentication requirements are met. **GO** for safe offline evidence and identity/privacy hardening.

No changes to main, public/session.php, deployed infrastructure, original APK, Nox, or official game services.
