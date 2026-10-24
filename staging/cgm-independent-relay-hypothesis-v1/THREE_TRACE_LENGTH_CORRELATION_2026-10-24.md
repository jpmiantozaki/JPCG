# CGM 1026 - three historical BASE-origin metadata clusters and command-length correlation

**Date:** 2026-10-24. **Scope:** read-only comparison of already archived, redacted October 8 `CGM_SESSION_REQUEST` and `CGM_SESSION_RESPONSE` metadata in user Library `CGM-Conversation-Backup-2026-10-08.txt`. No byte payloads, credentials, URL queries, device identifiers, relay tokens, live requests, APK or Nox operations.

## Why this checkpoint follows the seven-pair analysis

The existing `BASE_SEVEN_PAIR_NATIVE_OVERHEAD_MATCH_2026-10-24.md` and its 20/20-tested `PacketOverheadEvidence.php` correctly cover seven pairs from **13:49 UTC**. The earlier archive additionally contains **two distinguishable BASE-origin application-positive diagnostic clusters** at 13:33:07 and 13:33:32 UTC. These 13:33 records have not been established to represent two independently Garden-authenticated game sessions. They are historical relay observations only.

Each row is recorded as `request_bytes -> response_data_packet_bytes (delta)` and is paired within its respective transaction by the response's `request_stage` and `request_packet_bytes`.

| Chronological row | 13:33:07 UTC | 13:33:32 UTC | 13:49 UTC (known BASE-success trace) |
|---:|---|---|---|
| 1 / Stage 0 | 58 -> 65 (+7) | 58 -> 68 (+10) | 58 -> 63 (+5) |
| 2 / Stage 1 | 16 -> 21 (+5) | 16 -> 24 (+8) | 16 -> 21 (+5) |
| 3 / Stage 1 | 16 -> 21 (+5) | 16 -> 24 (+8) | 16 -> 28 (+12) |
| 4 / Stage 1 | 8 -> 17 (+9) | 8 -> 13 (+5) | 8 -> 14 (+6) |
| 5 / Stage 1 | 9 -> 17 (+8) | 9 -> 16 (+7) | 14 -> 26 (+12) |
| 6 / Stage 1 | 14 -> 24 (+10) | 14 -> 25 (+11) | 9 -> 20 (+11) |
| 7 / Stage 1 | Not recorded | 8 -> 17 (+9) | 8 -> 15 (+7) |

**20 paired exchanges across three clusters, not 20 independent login successes.** All 20 overheads are between 5 and 12 bytes, with observed distinct overhead values 5,6,7,8,9,10,11,12. These also fit the previously reported 3-12 byte overhead *range* of the **opposite-direction** original client `PacketEncryption.Decrypt` method. This is a range coincidence consistent with a variable-framing hypothesis, not proof of using that algorithm in the mobile intermediary.

## New discriminating deductions

1. **Same request size has different output sizes across traces.** For example, 58-byte Stage 0 requests return 65, 68, and 63 bytes, respectively. Therefore a deterministic padding formula depending only on request length cannot model all observed outputs.
2. **Even same-size requests within one cluster differ.** In 13:49, the two consecutive 16-byte Stage 1 requests yield 21 and 28 bytes. This rules out an unconditional fixed overhead *by stage and input length* for that cluster, but the requests might carry different commands and sequence fields, so it does not identify the missing input variable.
3. **Native senders offer candidate command labels, not proof.** Among six directly mapped Stage 1 senders, 16-byte requests can be `SendVerifyCCU` or `SendLogon`; 8-byte requests can be `SendPing` or `SendRequestUserInfo`; the 9-byte shape fits `SendRequestEnterChannel` and the 14-byte shape fits `SendRequestCoupleLevel` with a five-byte string. Other indirect/unmapped senders and alternate field shapes have not been excluded. The archived diagnostics do not record command bytes; never assert exact labels for those historic request rows from sizes alone.
4. **Order is not invariant.** At 13:33:32, the recorded 9-byte request precedes the 14-byte request; at 13:49 the 14-byte request precedes the 9-byte request. At 13:33:07, 9- and 14-byte requests overlap in time on separate local PHP workers. No universal mandatory order or channel/Garden authorization conclusion follows.
5. **Do not conflate Stage 0 continuity.** Both 13:33 Stage 0 request summaries say `relay_session_present=true`, whereas 13:49 Stage 0 says false. This is unusual for a fresh Stage 0 and makes a stronger user/session attribution for the earlier clusters inappropriate without trusted non-secret evidence.
6. All historical responses were documented as `upstream_host=anlgarden.com`, HTTP 200, protected JSON `error=false`, valid hex packet data, and a 12-byte relay-session field. The diagnostic `servertype=null` is **not** proof that the native response carried a null server type. An `error=false` relay envelope does not establish original game server acceptance.

## Engineering decision

**Maintain NO-GO for live zero-transform or guessed packet encryption.** This additional evidence rejects fixed overhead by packet size and makes variable packet preparation plausible. It does not supply a reversible cipher, a transformed packet, an authorized provider/session issuer, official account authentication, or Garden access.

The previous seven-pair analyzer and its tests remain applicable to that narrow source. Do **not** silently extend its `evidence_scope='one historical BASE origin session'` to these additional clusters. Any expanded offline assessor should explicitly distinguish trace clusters and their varying provenance before it accepts the 13:33 records.

**Highest-value next verified input**: legitimately authorized, privacy-reviewed command/response metadata including actual client command identifiers and game-service acceptance status; or licensed original/compatible server processor source. No credential-bearing GET logs, ciphertext or relay tokens, no live probes, and no repeated Nox, APK or synthetic-suite tests are justified by this checkpoint.

**Branch-only documentation.** No changes to `main`, `public/session.php`, production routing, APKs, Nox environments, or official game services.
