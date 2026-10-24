# CGM 1026 — native socket transport vs mobile relay transformation boundary

**2026-10-24.** Read-only follow-up on original ARM64 `libil2cpp.so` SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`. Direct `llvm-objdump` inspection; historic, already-sanitzed user diagnostics; no live traffic, credentials, account data, packet dumps, Nox, APK edits, or production deployment.

## Question addressed

Can JPCG's independent `session.php` simply put the unchanged Unity-produced framed request into `MGRelayResponse.data`, or must it prepare a different game-bound packet?

### Observed native outbound chain

1. `LoginServerManager.SendLogin` (RVA `0x6A80C0`) and GameServer senders serialize framed binary with `Packet.PrepareSize` (RVA `0x6BD050`). That method writes length of original framed packet, not of upstream's future output.
2. `RelayWebManager.SendRelayInfo` (RVA `0x6A82AC`) wraps a byte array as `packetHex~stage~relaySession` and invokes HTTP `RelayServerData` at `0x6A8448`.
3. `RelayWebManager.<RelayServerData>b__11_0` (RVA `0x6B088C`) handles protected `MGRelayResponse`: `0x6B0B50–0x6B0B58` converts the response `data` string from hex to bytes; `0x6B0B5C` reads `servertype`. If type 0 it calls `Session.Send` at `0x6B0C5C`, and if type 1 it calls `GameServerManager.SendPacket` at `0x6B0BB0`.
4. `GameServerManager.SendPacket` at RVA `0x69BF08` checks its underlying Session and directly branches to `Session.Send` (RVA `0x6BE850`). It does not itself add a header or encrypt a packet.
5. `Session.Send` at `0x6BE9A0` reads `sendEncryptionType` offset `+0x40`: nonzero modes 1 or 3 enter special preparation; normal mode **0** branches directly to `0x6BEAD0`, then calls the underlying stream's write routine at `0x6BEB18–0x6BEB34`. Prior original-client constructor study established send mode0 / receive mode2 on the login/game sessions. **There is no *client-side* post-HTTP transform in this normal outbound branch.**

### Different inbound layer — important to distinguish

`Session.OnReceive` (RVA `0x6BE528`) selects incoming `recvEncryptionType` and has a branch at `0x6BE74C–0x6BE754` calling `PacketEncryption.Decrypt(byte[])` (RVA `0x69974C`), consistent with earlier receive-mode2 mapping.

Inside that *inbound* decoder:
- At `0x699794`, reads a header byte at encrypted-packet offset 2.
- At `0x6997B4–0x6997C4`, derives a header-dependent value `k` and enforces `0 <= k <= 9`.
- At `0x6997A4–0x6997D0`, sets decoded output length to **`srcLength - 3 - k`**, then checks bounds and allocates the output at `0x699884–0x6998AC`. This describes variable inbound encryption/framing overhead of 3–12 bytes.
- This is **server-to-client receive decoding**. It does **not** establish that original BASE/MOD `session.php` uses the inverse algorithm to prepare **client-to-server** packets, nor that it adds exactly five bytes to Stage 0.

### Historical source-attributed metadata

Historic successful **BASE-origin** Stage 0 observation: original request **58 bytes**, protected HTTP response `data` **about 63 bytes**, successful reply including a **12-byte** mobile relay session, Stage 1 eventually observed. Historic MOD-member account sent to BASE origin: **57-byte** request and `error=true,message=vip`; no successful output for that account.

Consequence: at least one originally successful BASE session returned a packet whose **length differs** from its original request. The baseline `ZeroTransformRelayCandidate` that copies `$hex` into `MGRelayResponse.data` is **not a faithful reproduction of that observed BASE example**. The five-byte length delta cannot reveal byte positions, encryption key, possible counter rewrite, checksum, replay state, or a fixed universal rule. The inbound decoder's overhead formula must not be misapplied to an outbound relay.

## What is and isn't now implementable

**Confirmed useful:** original client request classifier, exact prefixed CGM membership preflight, client-native negative VIP rejection, reply shape inspection, all six directly mapped Stage 1 command senders, and observation that HTTP positive only instructs socket forwarding.

**Not established:** successful independent preparation of authentic game-bound outgoing packets; official service credentials and identity acceptance; mobile-session binding to official login state; whether the original relay transforms all commands similarly; whether zero-transform can work under a different genuinely authorized intermediary. A single request/response length pair and compiler-visible client code cannot reconstruct the missing server-side algorithm.

**Engineering decision:** **No deployable zero-transform success route.** Keep `ZeroTransformRelayCandidate.php` as CLI-only, labeled experimental and not connected to `public/session.php`. Do not implement constant `+5` padding or invert the inbound decryptor as if that were proven outbound server behavior. The next truly discriminating input is permitted, source-attributed, metadata-only actual game-service acceptance and command-paired request/response observations, or legitimately obtained original/authorized compatible processor source. We do not need another synthetic-suite count or Nox test at this stage.

**Safeguards:** Original `public/session.php` remains unchanged. No APK/Nox/production, third-party live requests or credential/token replay. 