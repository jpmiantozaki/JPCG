# CGM Auth-First — Phase 3.3Z Save State (2026-10-29)

**DO NOT repeat Phases 3.3S–3.3Y, reinstall the APK, or restart Winsock tracing.**

## Project purpose and separate channels

Existing original `com.DeathToB.ANLGarden` APK should recognize **CGM ACTIVE** via JPCG and subsequently use real official PlayPark login/GameServer/Garden sessions. CGM membership is **not** official authentication. The current Windows Go HTTP relay at `127.0.0.1:8091` forwards original mobile URLs to `https://jpcg.onrender.com`; JPCG `public/session.php` forwards the original protected request to `CGM_ORIGINAL_ORIGIN=https://anlgarden.com`. **Do not set that variable to JPCG (self-forwarding loop).** Original CONFIRM->`/authenticate.php` 200 (456 bytes), `/session.php` 200 (108 bytes), and original VIP-denied UI were observed; these are not accepted official game sessions. Production main/Render unchanged. Nox test `127.0.0.1:62033`; rollback `62030` MUST NEVER be modified.

## Relevant older milestones (do not redo)

- Original APK native two-button chain: outer yellow LOGIN -> `ConnectToServer(false)` RVA `0x6A8488`; inner CONFIRM -> `Login()` `0x6A86E8` -> `SendLogin()` `0x6A80C0` (call `0x6A8868`). `Login()` removes literal U+0020 space at `0x6A87FC`; `SendLogin` adds `INPP.`/ `INFB.` internally. Membership precheck works but does not substitute for official login.
- Phase3.0 owned upstream admission, atomic PostgreSQL one-use ledger and synthetic provider checks passed; it does not issue real sessions. Do not rebuild another viewer or admission framework.
- Windows Audition PE32 (`Audition_unprotected_extracted.exe`) imagebase `0x00400000`, SHA256 `a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911`.
- Phase3.3W PC protocol A: `send` storage `0x02EDFA98`, `recv` `0x02EDFA9C`; primary dispatcher `0x009E0050`, encoder `0x009E0740`, decoder `0x009E0580`; 229/230 complete captured LoginServer/GameServer frames decoded; one unmatched 800-byte inbound 18806 candidate. Two decoded outgoing login packets have lengths 57/55, counter 1, command `01 01`, three length-prefixed strings.
- Phase3.3X/Y PC protocol B for Garden 20840: alternative send `0x009E0170` slot5 and `0x009E01C0` slot7, `0x009E0CF0`; encoder header `0x009DE2E0`/body `0x009DE240`, decoder header `0x009DE1E0`/body `0x009DE120`. Correct Phase33Y body XOR uses ONE constant selected table byte per entire packet. The old Phase33X per-byte incrementing table index was WRONG, now superseded. 61/61 real Garden frames fully decode (36 test2 +25 cgm), all payload begin `C8`, no TCP gaps/unconsumed bytes.
- Original C8 packet type registration `0x00A69670`, handler vtable `0x00FD786C`, receive handler `0x00A6A9C0`, subtype dispatch tables `0x00A70088` and `0x00A6FF78`; subtype `02` native case `0x00A6AAE9`. Slots 5/7 get runtime host/port from different configurations; exactly which uses 20840 still needs correlation.

## NEW Phase3.3Z — full structural parsing for subtype C8 / 02

Uploaded full read-only trace `CGM_Phase33Y_C8_Handler_Trace.txt`, SHA256 `af23df0501c5132ff67f814755f4c3497819717c9d526f4f03e8186c0cd664a6`, confirms native case `0x00A6AAE9` reads:
1. Original packet LE16 size, command byte `C8`, subtype byte `02`, status byte `00`.
2. 21 bytes of unknown scalar fields in native order `u32,u32,u8,u32,u32,u32`.
3. THREE sections: `u32 count` then `count × 8-byte records`.
4. `u8 group count`; each group: 3-byte header whose third byte counts 3-byte records, then `u8 count` of 17-byte records, then `u8 count` of 10-byte records.
5. `u16 count` then `count × 18-byte records`.
6. Final 5-byte trailer.

Read-only original PCAP check against `test2.pcapng` and `cgm(1).pcapng` validated this ENTIRE schema, byte-for-byte length accounting, with zero unaccounted bytes:

| Capture | Size | Pair8 counts | Nested triplets | 17-byte records | 10-byte records | 18-byte records | Trailer |
| --- | --- | --- | --- | --- | --- | --- | --- |
| test2.pcapng | 5357 | 6/27/4 | 0 | 80 | 5 | 200 | 5 |
| cgm(1).pcapng | 5622 | 4/30/7 | 3 | 80 | 22 | 203 | 5 |

Both have status=0 and one nested group. The 80 records COULD be related to two 40-plant pages in an earlier screenshot, but **that interpretation is not verified**, and screenshot timestamps/account correspondence haven't been proven. Fields are unnamed/unknown; do not assign item names or quantities without evidence.

## New source

`staging/cgm-upstream-foundation-v1/src/CgmC8Subtype02Structure.php` accepts already decoded C8/02 data and returns ONLY record counts and offsets, with strict length checks. It DOES NOT return personal packet fields, authenticate anyone, or provide Garden session authorization. `tests/c8_subtype02_structure_test.php` has 63 synthetic assertions. An offline local check fed the two user's historic decoded packets directly via stdin into the PHP parser; **2/2 PASS**, zero trailing bytes, no raw content logged or committed. CI on this branch runs synthetic tests only.

## Next exact Phase3.3Z.1 engineering target

**Interpret the records, not the transport again.** Use the native `0x00A6AAE9` handler's 17-byte / 10-byte / 18-byte record processing, and read-only comparisons of the original two decoded C8/02 packets; identify the field offsets/types and map them to known Garden values only if capture-time ground truth is established. Build a schema-aware read-only parser with strict bounds and explicit UNKNOWN field labels. Prioritize the repeated 80 x 17-byte records and 200/203 x 18-byte records, plus the three 8-byte arrays. Native `0x00A6AAE9` assembly and decompiled function are preserved in the separately downloaded original full trace.

After semantics, continue protocol integration: genuine LoginServer accepted response on 18805 -> GameServer accepted session on 18806 -> Garden 20840; compare original mobile Stage0/Stage1 intermediary semantics with PC transport. Merely implementing transport decoder or returning positive `session.php` data does NOT prove a genuine official session. Production and real accounts remain untouched.

## GitHub save state

- Parent: `feat/phase33y-garden-full-decode-c8-handler` (PHP 26,944 synthetic tests CI PASSED, run 40811186715)
- New branch: `feat/phase33z-c8-02-structure-checkpoint`.
- This file: `staging/cgm-upstream-foundation-v1/PHASE33Z_C8_02_STRUCTURE_CHECKPOINT.md`.
- Local complete reconstructed conversation backup: `CGM_PROJECT_CHAT_BACKUP_AND_CHECKPOINT_2026-10-29.txt` (not a verbatim ChatGPT transcript); companion complete original uploaded Ghidra trace backed up separately in the offline ZIP. No original EXE, full raw Ghidra disassembly, original PCAP, credentials, decoded packet bytes, tokens, or proprietary lookup tables placed on GitHub.
