# CGM 1026 — original-command attribution for 20 historical BASE-origin relay exchanges

**Date:** 2026-10-24. **Branch:** `feat/phase40-staging-provider-interface`. **Status:** offline, read-only source evidence; no new network/game/emulator test.

## New evidence beyond the three-trace length study

The previous `THREE_TRACE_LENGTH_CORRELATION_2026-10-24.md` assigned only packet sizes. The **original user-owned October 8 access-log files** `Pasted text(3).txt` (13:33 UTC clusters) and `Pasted text(4).txt` (13:49 UTC cluster) also contain legacy protected HTTP *request* envelopes.

A targeted offline private inspection checked all **20** `/session.php?data=` request envelopes against the existing original-compatible SecurePacket checksum calculation. **20/20 checksum validations passed.** It recovered **only the first 16 hexadecimal characters of protected plaintext**, i.e. the first **eight bytes of the framed client packet**: u16 total frame length, u32 sequence (never logged or exported), and two command bytes. It then retained **only total frame length and command bytes**. All 20 frame lengths matched the already archived redacted request-size diagnostics.

No full decrypted request, username, password, sequence counter, private token, raw query, key, packet payload, or outgoing game packet was saved to GitHub or is reproduced below. The legacy checksum establishes only that a *stored protected envelope* matches its format; it is **not** proof of account ownership, authentic game response, or server acceptance.

## Command-attributed comparisons: original input → mobile relay output (overhead)

| Command identified in protected request | 13:33:07 UTC | 13:33:32 UTC | 13:49 UTC |
|---|---|---|---|
| `01 01` — Stage 0 Login | 58 → 65 (+7) | 58 → 68 (+10) | 58 → 63 (+5) |
| `00 16` — Stage 1 Verify CCU | 16 → 21 (+5) | 16 → 24 (+8) | 16 → 21 (+5) |
| `01 00` — Stage 1 Game Logon | 16 → 21 (+5) | 16 → 24 (+8) | 16 → 28 (+12) |
| `0A 00` — Stage 1 Request UserInfo | 8 → 17 (+9) | 8 → 13 (+5) | 8 → 14 (+6) |
| `09 00` — Stage 1 Request Enter Channel | 9 → 17 (+8) | 9 → 16 (+7) | 9 → 20 (+11) |
| `1F 0C` — Stage 1 Request CoupleLevel | 14 → 24 (+10) | 14 → 25 (+11) | 14 → 26 (+12) |
| `00 00` — Stage 1 Ping | Not recorded | 8 → 17 (+9) | 8 → 15 (+7) |

**Total: 6 + 7 + 7 = 20 request/response pairs.** The 13:49 cluster is the historical BASE-member session with observed Garden entry. The two earlier clusters are application-positive upstream exchanges but are **not independently established Garden-authenticated sessions**. The 13:33 Stage 0 request summaries already had `relay_session_present=true`; the 13:49 Stage 0 did not.

The 13:49 actual request ordering was `LOGIN → VERIFY_CCU → GAME_LOGON → USER_INFO → COUPLE_LEVEL → ENTER_CHANNEL → PING`. In both 13:33 clusters, `ENTER_CHANNEL` preceded `COUPLE_LEVEL`; in the first two, the logon/verify calls produced the same response lengths. The previous ordering differences were real, not just ambiguous assignments.

## What is now proven — and not

1. All 20 recovered command IDs match one of the six already mapped Stage 1 Unity sender methods or the previously mapped Stage 0 login command. There were no unrecognized command IDs **among this archived sample**. This does not exclude other indirect senders.
2. **Fixed overhead by packet length or command is rejected for these observations:** the *same identified command* has varying returned packet lengths across clusters. E.g. Login `01 01` adds +7, +10, +5 bytes; `01 00` Game Logon adds +5, +8, +12 bytes. Therefore a universal deterministic padding rule based solely on the command and original frame size cannot reproduce this sample.
3. All 20 deltas are within the previously traced **3–12 byte** overhead range of the original client's *opposite-direction* `PacketEncryption.Decrypt`. This is consistent with, but **does not prove**, shared framing/encryption. No output header or transformed bytes were available to test that mechanism.
4. An application-positive `MGRelayResponse` is not proof of official LoginServer, GameServer, or GardenServer acceptance. Only the historical 13:49 user-observed Garden outcome provides outcome evidence for that BASE-member run, not CGM-only integration.
5. The original `servertype=null` diagnostics indicate a parser-level unavailable value, **not** proof of a native null `servertype`.

## Engineering decision and next information gate

**Command attribution is no longer the blocker for these 20 historical requests.** Do not rerun packet classification, repeat synthetic suites, capture new credentials, attempt third-party live login, or guess a fixed `+5`/variable padding scheme.

The remaining unknown is the **authorized upstream’s transformation of game-bound output bytes and the corresponding genuine game-service acceptance/identity/session binding**. Future progress would require legitimately obtained compatible intermediary source or strictly authorized, privacy-reviewed output-header/accepted-response evidence. Do not reverse the client's *inbound* decryptor without direction-specific proof.

**No modifications:** `main`, production `public/session.php`, original APK, Nox, official game services, and earlier staging prototype remain unchanged.


## 2026-10-24 correction: embedded packet counters resolve apparent order

Subsequent read-only inspection of the original eight-byte framed request headers recovered the per-session packet counter without inspecting credentials or payload bodies. In every archived cluster, Stage 1 `REQUEST_COUPLE_LEVEL` (`1F 0C`) bears counter **4**, while `REQUEST_ENTER_CHANNEL` (`09 00`) bears counter **5**. Consequently, the earlier observation that channel entry appears first in some **HTTP arrival logs** must **not** be interpreted as evidence that the Unity client *constructed* channel entry first. Concurrent HTTP requests can be logged out of packet-creation order. This correction is documented in [`FRAME_COUNTER_VS_HTTP_ARRIVAL_AUDIT_2026-10-24.md`](FRAME_COUNTER_VS_HTTP_ARRIVAL_AUDIT_2026-10-24.md).

The embedded counter also rules out a transformation length determined solely by command, original frame length and packet counter: identical command/counter combinations have different output overheads across historical clusters. This does not prove a particular encryption algorithm or official server acceptance.
