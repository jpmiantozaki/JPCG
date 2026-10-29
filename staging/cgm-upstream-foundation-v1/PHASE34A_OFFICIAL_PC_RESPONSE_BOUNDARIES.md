# Phase 3.4A — Original PC login/GameServer response-boundary audit

**2026-10-29 — Read-only archived PC traffic. This does NOT demonstrate independent CGM APK login or game-session issuance.**

## Why Phase 3.4A was necessary

The prior Phase 3.3W analysis matched **229/230 complete TCP-segment-level candidate frames** on ports 18805/18806, leaving one apparent 800-byte GameServer response. It did not reassemble every complete TCP stream before interpreting those length fields. Phase 3.4A reuses the already-recovered Windows codec A and performs stream-level TCP reconstruction; no new cipher, credential parser or network client.

## Verified original capture results

Two user-owned existing recordings: `test2.pcapng` (1,467,112 bytes), `cgm(1).pcapng` (136,620 bytes). The original Windows PE32 executable was previously verified as SHA256 `a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911`.

| Capture | Login C2S | Login S2C | Game C2S | Game S2C | Total |
| --- | ---: | ---: | ---: | ---: | ---: |
| test2 | 1 | 1 | 67 | 150 | **219** |
| cgm(1) | 1 | 1 | 6 | 19 | **27** |
| TOTAL | 2 | 2 | 73 | 169 | **246** |

**246/246 reassembled transport frames** decode with the verified native Windows opcode-A wrapper and exact inner LE16 size. Both streams have **zero** detected gaps and **zero** leftover bytes. This does not mean all 246 represent accepted authentications; many are gameplay operations and server replies.

**The old 800-byte anomaly is a false candidate.** In `test2.pcapng` one inbound GameServer TCP *segment* begins within an already-framed message. The first two bytes at that mid-packet position can be interpreted as a plausible LE16 length 800, but that position is **not in the set of true reconstructed frame starts**. The correct continuous server stream consists of 150 decodable frames, with zero partial remainder. Therefore do not invent a separate 800-byte codec.

A critical **directional asymmetry** was confirmed: client-to-server original frames have a verified `[u16 LE length][u32 packet counter][two-byte command]` prefix; we are **not** entitled to read bytes `6..7` from server-to-client inner data as an opcode. Those bytes may include personal account or session material and are never printed, parsed into commands or committed. Incoming frames as short as 5–7 bytes are valid transport frames in this capture (26 in test2, 7 in cgm), not errors for the client-only 8-byte command grammar.

Outgoing verified original PC requests in BOTH captures contain:

| C2S command pair | Prior native meaning |
| --- | --- |
| `01 01` at LoginServer 18805 | Initial LoginServer submission |
| `00 16` at GameServer 18806 | VerifyCCU |
| `01 00` at GameServer 18806 | Game Logon |
| `0A 00` at GameServer 18806 | RequestUserInfo |
| `09 00` at GameServer 18806 | Channel operation |
| `1F 0C` at GameServer 18806 | Couple-related request |
| `00 00` at GameServer 18806 | Ping |

The archive also contains actual S2C frames, but **these numeric frame-size and ordering observations do not prove that a new CGM mobile attempt is officially accepted**. Prior Phase 4.24/4.28 confirmed original PC Garden read-only observation; the archived PC client owns its legitimate session.

## Deliverables and tests

- Conversation ZIP `CGM_Phase34A_Official_PC_Response_Boundaries_2026-10-29.zip`, SHA256 `7ce078cd4bdb52629659251faa663bdb048d5acff0fd00deec0ab7c5c706668c`, includes full bounded Python PCAP metadata audit, independent segment-boundary classifier, reused verified Phase33W reference codec, **16** passing synthetic offline unit tests, and privacy-safe capture metadata reports.
- This GitHub branch includes a self-contained Python stream/codec audit `research/phase34a_pc_stream_metadata.py` and synthetic regression test `tests/phase34a_pc_stream_metadata_test.py`. GitHub tests are run with **invented** frames only; proprietary captured data and executable binaries are never committed.
- Code never makes HTTP/TCP requests, captures live traffic, logs decoded response bodies, credential text, arbitrary server bytes, session identifiers, IPs or account information.

## Exact remaining official-login/session dependency

- `public/authfirst.php` verifies CGM ACTIVE membership, not game identity.
- `public/session.php` forwards original credential-bearing protected envelopes to `CGM_ORIGINAL_ORIGIN=https://anlgarden.com`, whose independent VIP requirement still applies.
- Phase 3.0 `ProviderHandoffGate.php` and one-use admission ledger work only with a trusted independently attested original account and provider permission; defaults correctly deny.
- Phase 40CE statically mapped original Android response branches: LoginServer OnPacketReceived, CCU, Game Logon, UserInfo, Channel, Garden login and Garden load. No authorized genuine **CGM-owned** provider session and no correctly correlated actual mobile handler acceptance sequence have been demonstrated.

**Phase 3.4B** should obtain permissioned, independently verified original-provider acceptance and same-identity binding from an authorized interface, with attempt/connection-generation correlation. Do NOT treat a correct cipher, frame, positive HTTP relay envelope, CGM ACTIVE, or old PC Garden capture as a newly valid game/Garden session. Never turn original VIP denial into false success.

**No default branch, production Render/Neon, original signed APK, Nox62033, or rollback Nox62030 changes.**
