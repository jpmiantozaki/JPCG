# Phase 3.3Y — Full Garden transport decoding + C8 native handler

Date: **2026-10-29**. This supersedes Phase 3.3X's partial Garden body-decoding algorithm. The work remains isolated to a source branch.

## Corrections now verified

- Original Windows `FUN_009DE240` encoder instruction `0x009DE2C3: xor al,[edx+8]` and `FUN_009DE120` decoder `0x009DE1C4: xor al,[edi+4]`: **same selector-selected key byte used for every body byte**; register holding table address does not advance.
- Previous Phase33X Python/PHP `lookup[selector+i]` logic was wrong. Its 55/61 complete count and 6 long-frame limitations are retracted. It passed cross-language parity because both tools contained the same error.
- The corrected `src/CgmGardenModeBPacket.php` now returns `FULLY_DECODED` and complete `decoded_payload` for all valid frames. The prior `decoded_prefix` and `decoded_prefix_bytes` keys remain backwards-compatible aliases, now pointing to the complete result.
- Original user-owned capture audits against exact original PE32 hash: `test2.pcapng` **36/36 full**, `cgm(1).pcapng` **25/25 full**, total **61/61** full Garden 20840 frames, including 239, 277, 345, 530, 5357, 5622 bytes. First payload byte **0xC8 in all 61**; TCP gaps/trailing bytes **0**.
- Python/PHP read-only capture parity: **61/61** full frames (0 prefix-only), all C8; original packet bytes, proprietary lookup tables, credentials, tokens, IPs never printed or committed.

## New native dispatcher and provenance

- The Windows client registers top-level packet type `0xC8` via `0x00A69670 -> FUN_009DF0A0`; class vtable `0x00FD786C` has virtual receive handler at `0x00FD7870 -> 0x00A6A9C0`.
- Handler `0x00A6A9C0` reads the next payload byte as a subtype, indexed through `0x00A70088` and jump table `0x00A6FF78`.
- Exact original native destinations: subtype `0x02` -> `0x00A6AAE9` (**5357- and 5622-byte response frames**); `0x2A` -> `0x00A6BB7B`; `0x1D` -> `0x00A6EC6C`; `0x66` -> `0x00A6F58B`. These are switch destinations, NOT yet decoded Garden item semantics.
- Socket slot 5 connection helper `0x009DF260` called from `0x004AC2F7`: server list with 0x42-byte entries, port at entry +0x40. Slot 7 helper `0x009DF9D0` called from `0x008F4843`: host `0x01C5917E`, port `0x01C591BE`; these values are populated at runtime. Slot 5 is consistent with captured subtype `0x66` but is not positively tied to remote Garden 20840 in this static audit.

## Evidence/tests and next step

- Corrected staging PHP code + regression tests: **26,944 synthetic assertions PASS locally**; CI workflow added for same suite.
- Original PE32 byte assertions, five Python test groups and metadata-only all-61 Garden capture validation: PASS offline.
- Full download package `CGM_Phase33Y_Garden_C8_Full_Decode_2026-10-29.zip` (sha256 `f3d06e55753f9c8f6fcf97e6ab0add57346c4e85346fab0a657af814f20fda8a`) includes corrected Python, PHP, read-only capture audit, and focused PyGhidra `CGM_Phase33Y_C8_Garden_Handler_Trace.py`. No user executable/captures or raw packet contents included.
- **Phase 3.3Z:** decompile `0x00A6A9C0` with focus on subtype `0x02` at `0x00A6AAE9`; derive actual field structure of the two 5K+ Garden responses and only then implement real inventory parsing.

We have a completed Garden **transport decoder**, not completed CGM upstream session authorization, original PlayPark authentication, or Garden inventory semantics. Original APK, Nox, JPCG production main branch and Render unchanged.
