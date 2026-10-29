# CGM Phase 3.4B — Original PC native Login/Game response branches

**Date:** 2026-10-29 (Asia/Manila). **Scope:** read-only exact archived Windows PE32 and two existing user-owned PCAPNGs. No mobile login, Nox, live game connection or production change.

## Key new finding

Phase 3.4A established 246/246 properly TCP-reassembled native transport frames, but could not interpret server-to-client (S2C) response bytes safely using the client-to-server command offset. This phase resolves that asymmetry from the **actual original Windows receiver**.

Original binary SHA-256 `a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911`, size 46,613,504 bytes, image base `0x00400000`. Version-pinned checks were executed locally:

- Receiver `0x009DFAE0` invokes transport decoder `0x009E0E80` for non-Garden sockets.
- `0x009E0FC0` decrements the decoded frame length by two and places the read cursor at **decoded inner frame +2**.
- `0x009DFBD3` loads the first payload byte from that cursor, then `0x009DFC04` indexes the 256-handler table at `0x02EDFAF0`. This byte is **decoded inner offset 2**, not the outgoing-only offset 6.
- Registration function `0x009DF0A0` stores handlers in `0x02EDFAF0 + opcode*4`. Registration at `0x00408A32` installs `0x01` with object vtable `0x00FD71C8`; vtable +4 points to **`0x00A1F6D0`**. Separate registered handlers: `0x00 -> 0x009E3640` at `0x004096C2`, `0x30 -> 0x00A1A490` at `0x00407BC2`.
- Handler `0x00A1F6D0` reads the next two decoded bytes at offsets **3,4**, into subtype (`bh`) and result (`bl`), respectively. `0x00A1F71D` and `0x00A1F725` distinguish subtype 0/result 0. At `0x00A1FF2E`, `cmp bh,1` and `0x00A1FF37` `test bl,bl` distinguish subtype 1/result 0. These are genuine original PC client **zero-result code branches**, not proof of independent mobile login.

Static audit: **17/17 byte-pattern checks** plus **3/3 native vtable-handler checks** passed on the exact original executable.

## Archived PC capture observations (not login credentials)

| Source | LoginServer `18805` response | GameServer `18806` response | Assessment |
| --- | --- | --- | --- |
| `test2.pcapng` | `01 / 01 / 00` present once | `01 / 00 / 00` present once | Matches original PC handler zero-result branch predicates |
| `cgm(1).pcapng` | `01 / 01 / 00` present once | `01 / 00 / 00` present once | Same result |

The logged labels refer ONLY to dispatcher opcode/subtype/result and archive framing metadata; all other server payload fields are deliberately ignored. Original C2S requests `0101` (login), `0016` (VerifyCCU), `0100` (Game Logon), `0A00` (UserInfo), `0900` (Channel) were already captured in Phase 3.4A. Both PCAPs have **zero TCP gaps and zero unconsumed bytes**, and retain **219 / 27 decoded frames**.

For historical context, separate Phase 40CE Android static analysis identified `LoginServerManager.OnPacketReceived` RVA `0x6A738C` and `GameServerManager.Packet01_Login` RVA `0x69AB94` with success-style branches. The PC response structure aligns conceptually with these client handler roles, **but the original Android binary has not been observed consuming these specific PC-origin frames**.

## Source/tests in this branch

- `research/phase34b_native_pe_invariant.py`: verify exact original PC hash, 17 native code instructions, and 3 vtable handler entries; fail closed on variant executables. No original EXE committed.
- `research/phase34b_server_branch_audit.py`: reuses `phase34a_pc_stream_metadata.py`; reads user-owned archives locally, classifies only static-supported PC native branch conditions and previously verified C2S opcodes; NEVER emits payloads, IPs, session identifiers, passwords, arbitrary upstream fields or packet counters.
- `tests/phase34b_server_branch_test.py`: synthetic examples for positive and negative native branch predicates, framing, privacy, and denial of claims about new mobile authorization.
- A downloadable separate conversation ZIP also includes the original Phase34A offline dependency and privacy-safe **aggregate** PCAP audit results. No real PCAP or proprietary EXE in ZIP.

## What is still missing for CGM Auth-First

**No newly authenticated CGM-owned official PlayPark/GameServer/Garden session has been demonstrated or issued.** PC native handler success-style branches in historical recordings are observations of the original PC client, NOT an independent authorization response to a new CGM APK attempt. They do not attest account ownership, link a CGM membership email to a provider subject, or override the original third-party VIP decision.

Actual JPCG `public/session.php` is a pass-through to `CGM_ORIGINAL_ORIGIN=https://anlgarden.com`. Phase 3.0 `ProviderHandoffGate` fails closed without a genuine trusted provider/identity link; this phase does not implement a fake provider or invent game-bound response packets. Phase 40CE has already mapped original Android native handler symbols; **do not repeat those native scans** without new runtime/authorization evidence. Previous Phase 4.28 official PC read-only Garden observer is a different working original-client route.

## Next target: Phase 3.4C, actual mobile acceptance provenance

Use a permissioned original mobile login or a provider-supported verifier to correlate **actual mobile native server-origin handler events** (LoginServer -> Game VerifyCCU -> GameLogin -> UserInfo -> Channel -> GardenLogin -> GardenData) to the **same authenticated provider account, login attempt and socket generations**, with no sensitive data logging. Phase 40CE's synthetic event checker can provide the existing shape/ordering framework but cannot create independently trustworthy evidence. A real provider session/entitlement grant is still an external required dependency. Do not turn `VIP` rejection into success; do not treat a historical PC response as a reusable token.

No JPCG main, Render, Neon, original APK, Nox62033 or rollback Nox62030 modifications.
