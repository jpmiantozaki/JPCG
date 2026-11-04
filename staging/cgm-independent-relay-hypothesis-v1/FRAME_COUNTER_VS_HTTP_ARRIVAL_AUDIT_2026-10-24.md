# CGM 1026 — original frame counters reveal logical request order despite concurrent HTTP arrival

**Date:** 2026-10-24. **Branch:** `feat/phase40-staging-provider-interface`. **Read-only, offline investigation.** Follow-up to `TWENTY_REQUEST_COMMAND_ATTRIBUTION_2026-10-24.md`. No original-game connection, APK, Nox, credential submission, public route or production change.

## Source and privacy boundary

Original archived, user-owned October 8 diagnostic/access files: `Pasted text(3).txt` (two 13:33 UTC clusters) and `Pasted text(4).txt` (13:49 UTC cluster). For each `session.php` access-log request, locally validated the existing legacy SecurePacket checksum and decoded only the **first eight framed request bytes** from the protected `packetHex~stage~relaySession` envelope: u16 length, u32 packet counter, and two command bytes. It inspected **no username, password, relay token or packet body**. Paired each request with its nearby corresponding diagnostic response by frame size, request stage and log-position proximity; every match found uniquely within a short local window.

**Results: 20/20 protected request checksums valid; 20/20 original frame lengths matched the corresponding response-side request size; 20/20 request→response metadata pairs resolved.** This is archive consistency, NOT new game server acceptance, a cipher test, or an authenticated exchange.

## Logical counter sequence (all clusters)

| Command | Native meaning | Observed frame counter |
|---|---|---:|
| `01 01` Stage 0 | Login | 1 |
| `00 16` Stage 1 | Verify CCU | 1 |
| `01 00` Stage 1 | Game logon | 2 |
| `0A 00` Stage 1 | Request UserInfo | 3 |
| `1F 0C` Stage 1 | Request CoupleLevel | 4 |
| `09 00` Stage 1 | Enter Channel | 5 |
| `00 00` Stage 1 | Ping | 6 |

The 13:33:07 cluster ends before Ping; the 13:33:32 and 13:49 clusters have all six Stage 1 commands. The 13:33 Stage 0 request diagnostics already showed `relay_session_present=true`; only the 13:49 Stage 0 is identified as having no incoming mobile relay session. Counter reset (Stage 0: 1; Stage 1 Verify CCU: 1) is compatible with separate login/game session packet counters but does not alone establish their runtime ownership or authentication state.

## Material correction to older interpretation

`THREE_TRACE_LENGTH_CORRELATION_2026-10-24.md` correctly reported **HTTP log arrival** of Enter Channel (`09 00`, 9 bytes) preceding CoupleLevel (`1F 0C`, 14 bytes) in both 13:33 clusters. The embedded **original packet counters reverse that apparent order**: CoupleLevel counter 4 precedes Enter Channel counter 5 in all three clusters. At 13:33:07 the requests also overlapped between different local PHP workers.

**Do not infer client packet-construction order from the HTTP arrival order alone.** Native packet counters provide stronger evidence of logical packet construction order. They do not prove wire delivery order or original service acceptance; parallel HTTP processing is a plausible reason for the reordering, not a fully observed causal explanation.

## New transformation constraint

Across clusters, the **same original command, request size and frame counter** frequently has *different* returned relay-packet lengths. For example:

| Command (counter) | 13:33:07 overhead | 13:33:32 overhead | 13:49 overhead |
|---|---:|---:|---:|
| Login `01 01` (1) | +7 | +10 | +5 |
| Verify CCU `00 16` (1) | +5 | +8 | +5 |
| Game logon `01 00` (2) | +5 | +8 | +12 |
| UserInfo `0A 00` (3) | +9 | +5 | +6 |
| CoupleLevel `1F 0C` (4) | +10 | +11 | +12 |
| Enter Channel `09 00` (5) | +8 | +7 | +11 |
| Ping `00 00` (6) | Not observed | +9 | +7 |

**Seven of seven command/counter combinations that recur in multiple clusters have varying overhead.** This refutes a length rule derived *solely* from original command, packet length and packet counter. It does **not** show that the complete original input bytes are identical, nor distinguish per-session randomized framing, a payload-dependent transformation, nonce state, or an unrelated permitted algorithm.

All observed deltas still fit the known 3–12-byte inbound-decrypt overhead *range*. Since the inspected decryptor handles **game-to-client** traffic and relay output is **client-to-game**, matching ranges do not identify outbound encryption, keys or server behavior.

## Decision

**Preserve the reconstructed logical command order** as an evidence-backed client-side sequence for future tests of a *legitimately authorized* processor. The missing independent-CGM interoperability requirement remains compatible **game-bound output packet preparation** plus authenticated, provider-issued identity/session continuity and original game-server acceptance.

**Do not** implement guessed output padding, invert the native inbound decryptor as a substitute for server source, mint relay/login tokens or infer genuine game authorization from response `error=false`.

Only sanitized derived command/counter/length metadata is retained in this document. No raw protected URLs, credentials, packet bytes, checksums, encryption key, session fields or account-specific data is committed. Earlier synthetic-suite PASS results remain unchanged; no suite was rerun.
