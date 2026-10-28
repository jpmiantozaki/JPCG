# CGM 1026 independent upstream — Stage 1 completeness audit

**Date:** 2026-10-24. **Scope:** original Unity/IL2CPP ARM64 offline, read-only; CGM staging source review; no game-server traffic or production changes.

## What changed materially

The previous zero-transform staging candidate recognized only two Stage 1 packet commands: `SendVerifyCCU` and `SendLogon`. A targeted native audit now maps **all six directly located original HTTP-relayed GameServer sending methods**. Four Stage 1 command types were missing, including **`SendRequestEnterChannel`**, whose reply handler contains the Garden connection transition. Therefore the existing zero-transform candidate is **structurally incomplete for normal Garden entry**, even before considering whether the official LoginServer/GameServer would accept its byte-identical packets.

## Original native evidence

Source binary `libil2cpp.so`, SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`. Inspected with `llvm-objdump -d --start-address...` in read-only mode; matched method names from original recovered `dump.cs`.

All listed game senders call or tail-branch to `RelayWebManager.SendRelayInfo` (`0x6A82AC`) with `w2=1` (Stage 1). Each uses the original packet writer and `Packet.PrepareSize`: little-endian u16 total frame length, little-endian u32 counter, 2-byte command, then payload. These lengths refer to the *client request before mobile intermediary processing*, not returned `MGRelayResponse.data`.

| Game sender | Method RVA | Call/tail site | Cmd hex | Original framed bytes | Payload |
|---|---|---|---|---|---|
| `SendPing()` | `0x69B8C8` | `0x69B9C4` | `00 00` | 8 | no payload |
| `SendVerifyCCU()` | `0x69A504` | `0x69A6B8` | `00 16` | 16 | u32 userSN, u32 CCU key |
| `SendLogon()` | `0x69B9D8` | `0x69BB8C` | `01 00` | 16 | u32 userSN, u32 CCU key |
| `SendRequestEnterChannel(byChannelNumber)` | `0x69BBA4` | `0x69BCB4` | `09 00` | 9 | one byte channel number |
| `SendRequestUserInfo()` | `0x69BCC8` | `0x69BDC4` | `0A 00` | 8 | no payload |
| `SendRequestCoupleLevel(szIGN)` | `0x69BDD8` | `0x69BEF4` | `1F 0C` | 9 + N | one-byte-length-prefixed string, N bytes |

The seventh original `SendRelayInfo` direct branch is Stage 0 `LoginServerManager.SendLogin` at `0x6A82A4`, not another Stage 1 sender. This covers **direct** branches enumerated in the prior archived xref audit; additional indirect/delegate paths remain possible.

### Why channel entry matters for Garden

Original `GameServerManager.Packet09_EnterLeaveChannel` is at `0x69AD18`. The native response handler reads a byte and compares it to **`0x0A`** at `0x69AD78`, then has a conditional branch toward Garden at `0x69AE48: bl 0x66CCE8` (`GardenServerManager.ConnectToGardenServer`). This is a server-reply-dependent transition, not an automatic consequence of sending `09 00` or receiving an HTTP `error=false` envelope.

The prior candidate **fails closed** for `09 00` and other additional Stage 1 types (`UNMAPPED_STAGE1`), so it could never independently complete this normal path as written. This is not a reason to turn these into synthetic authorized success; the correct response and provider state are still unknown.

## Another independent reason to doubt zero-transform as the original protocol

An existing archived successful **BASE-origin** eligible attempt contained an approximately 58-byte original Stage 0 client request and approximately 63-byte `MGRelayResponse.data` packet. They **cannot be byte-identical for that observation** if the lengths are correct. That is evidence against *unconditional byte-for-byte identity as the original BASE upstream's behavior*. It does not prove a reusable five-byte append/header, nor that every possible packet must change, nor what a valid PlayPark socket packet looks like.

Older sanitized Stage 1 reply lengths of 22/25/17 bytes lacked trustworthy origin and matched-request attribution. Do not map them to a particular command or infer transformations from those alone.

## Code and testing added (actual source committed to JPCG)

- `src/Stage1CoverageInspector.php`: safe, offline read-only classifier for all six directly observed Stage 1 client request frame shapes; redacts userSN, CCU, strings, counters and relay secrets. It never sends bytes or creates a positive response, token or authentication claim.
- `tests/stage1_coverage.php`: **39/39 PASS** locally, using synthetic request frames; PHP syntax PASS.
- `src/ZeroTransformRelayCandidate.php`: renamed historical `verified` boolean to `ccu_request_seen`. This is a *simulated observation that an outbound CCU request was processed*, **not** a genuine original GameServer verification acknowledgment. Previous independent zero-transform local regression **38/38 PASS** after rename.
- No downloadable package needed; both source files and tests are in JPCG staging.

## Engineering decision

1. **NO-GO**: Treat `ZeroTransformRelayCandidate`'s synthetic `error=false` as a deployable independent `session.php`, or infer authentic game acceptance from its staged token/state.
2. **GO**: Use six-command static classification as coverage evidence and to inspect a properly authorized provider's already-produced packet shapes, without exposing secrets.
3. **Missing proof**: Actual authorized provider processing and real server-accepted socket packets; client-side binary inspection alone does not reveal private upstream transformation or prove it can be omitted.
4. **Most valuable next evidence**: A *permitted*, origin-attributed successful Stage 0 / Stage 1 interoperability record including only sanitized command type, request/response packet lengths, response `servertype`, and subsequent original game-server acceptance status. No full packet bytes, passwords, account IDs, tokens, ciphertext URLs or sensitive packet contents should be shared. Alternatively, the legitimately obtained compatible original intermediary implementation or its supported protocol contract.

**No new APK, Nox, production routes, credentials, PlayPark connection, external network probe or modified `public/session.php`.**
