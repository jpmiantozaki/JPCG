# CGM original Unity native packet writers — staging codec checkpoint (2026-10-24)

**New implementation:** Conversation artifact `CGM_Unity_Client_Packet_Contract_v1.zip`, SHA256 `f1c1169d9d4f1a02dfebc0629a98c23532b919459eff3e6d69e8404d47aafb88`. Includes PHP read-only codec `src/ClientPacketInspector.php`, `tests/run.php`, README, and validation report. Local syntactic checks passed; synthetic **34/34 PASS**; ZIP integrity passed. Source file SHA256 `42faeb6bc6f4790df3dd5a6a2aa7a1bff0ca1562e50f41d23464ca14ab29c1c9`; test file SHA256 `1c0bd1d592402d149a26dc03b172d74f526a38f52589a064b58c63e5e1ef8765`. Actual implementation is in the conversation ZIP, **not automatically deployed or committed as source in this repo**.

## Original-client evidence
Read-only direct ARM64 inspection of original CGM `libil2cpp.so` (SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`, the previously verified identical BASE/MOD client library).

- Native `Packet.PrepareSize` `0x6BD050` writes total frame byte length as u16 little-endian.
- Original packet header: u16 frame length, u32 client sequence, two u8 command bytes.
- `LoginServerManager.SendLogin` `0x6A80C0`: `WriteByte(1)`, `WriteByte(1)`, three `Packet.WriteSafeString` calls (`0x6BCF18`, one-byte length prefix), for prefixed login ID, password and client version; then `RelayWebManager.SendRelayInfo(stage=0)`.
- `GameServerManager.SendVerifyCCU` `0x69A504`: `WriteByte(0)`, `WriteByte(22)`, then `WriteDWord(userSN)`, `WriteDWord(CCU)`; frame size 16 bytes, `SendRelayInfo(stage=1)`.
- `GameServerManager.SendLogon` `0x69B9D8`: `WriteByte(1)`, `WriteByte(0)`, then same two DWORDs; frame size 16 bytes, `SendRelayInfo(stage=1)`.
- HTTP relay wraps binary in the already-documented `packetHex~stage~relaySession` envelope. Login stage0 has no previous session, stage1 uses a prior relay session.

## What is now implemented
Read-only `CgmClientPacketInspector::inspectEnvelope` / `inspectBinary` classify known native client request frames while returning only safe metadata, not account identifiers, password strings, CCU, token, or sequence. It recognizes normal INPP and Facebook INFB namespaces as format indicators **without asserting identity ownership**, checks exact two-DWORD size for the two known stage1 commands, rejects malformed frames and reports unrecognized commands without treating them as authenticated. Uses synthetic fixtures only.

Test under Windows (after extracting ZIP into `D:\CGMTEST`):
`& 'D:\CGMTEST\PHP\php.exe' 'D:\CGMTEST\CGM_Unity_Client_Packet_Contract_v1\tests\run.php'`
Expected `ALL 34/34 PASS`. No need to rerun previous 38/38 identity checks.

## Trust and remaining dependency
**This is client REQUEST decoding, NOT a working `session.php` server processor.** It does not decrypt existing SecurePacket itself, issue `MGRelayResponse.data` or `.session`, authenticate official PlayPark accounts, mint userSN/CCU, or bypass VIP. Earlier v3.1 request-validation only knew stage1 as opaque; this is a genuine additional native command-level finding. Existing stage/type equality remains unproved for original successful responses. A legitimate authorized session-processing interface and official service acceptance remain prerequisites.

**No APK/Nox/PlayPark/public backend modifications or live requests.** Preserve unchanged client login and optional PIN behavior. No real credentials were logged or used.
