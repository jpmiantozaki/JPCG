# CGM Auth-First — Phase 3.3Z.1, Garden C8/02 native field layouts

**2026-10-29.** Continue from Phase3.3Z without restarting the socket/cipher/Unity investigation. A new **read-only typed PHP parser** is available; it does not authenticate, contact real servers or assume inventory meanings.

## Verified original sources

- Same owner-provided Windows executable `Audition_unprotected_extracted.exe` SHA256 `a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911` (PE32 imagebase `0x00400000`).
- Complete existing static Ghidra trace `CGM_Phase33Y_C8_Handler_Trace.txt`, case `0x00A6AAE9` in handler `0x00A6A9C0`, plus parent Phase33Y/33Z checkpoint and exact TCP reassembly/ciphers.
- Original historic Garden port-20840 PCAPNG responses: 5357 and 5622 decoded bytes. Existing Phase33Z structural parser accounts for **every byte** in both, trailer included.

## NEW typed schema (offsets relative to each record)

| Record | Native verified byte layout | Field meaning confidence |
|---|---|---|
| 8 bytes, 3 counted sections | `[u32 field0][u32 field4]` | Two LE32 values confirmed. Whether ID/quantity remains unknown. |
| 3 bytes | `[u8][u8][u8]` | Exact widths confirmed; semantics unknown. |
| 17 bytes | `[u8 slot_index][u32 unknown][u8 group_selector][u8 coord_a][u8 coord_b][u8 state_8][u8 state_9][u8 state_10][u8 state_11][u8 state_12][u32 tail]` | The native handler uses the selector to choose one of two buffers; 10x4 grid pattern strongly supports coordinate meaning. Plant/item ID and state meanings unproven. |
| 10 bytes | `[u8 slot_index][u32 unknown][u8 group_selector][u8 field6][u8 field7][u8 field8][u8 field9]` | Consecutive indices after 79, exact widths confirmed. Semantics unknown. |
| 18 bytes | `[u8 slot_index][u32 array_selector][u32 unknown_a][u32 unknown_b][u8 class][u32 unknown_c]` | Native handler writes at array base + `slot_index*0x1c`, chosen by selector; class = 0/1/other increments three counters. Names and significance of values still unknown. |

Fields are little-endian when wider than one byte. The fixed 21-byte packet header and final 5-byte trailer remain opaque, and are never shown by the parser diagnostic.

## Cross-capture structural facts (real decoded captures, no invented values)

| Check | `test2.pcapng` | `cgm(1).pcapng` |
|---|---:|---:|
| C8/02 packet length | 5357 | 5622 |
| 8-byte array counts | 6 / 27 / 4 | 4 / 30 / 7 |
| 17-byte records | 80 | 80 |
| Group 0 and 1 (17-byte) | 40 / 40 | 40 / 40 |
| Unique X values × unique Y values per group | 10 × 4 | 10 × 4 |
| Unique coordinate pairs per group | 40 | 40 |
| Consecutive extra 10-byte record indices | 80–84 (5) | 80–101 (22) |
| 18-byte array selector 0 / selector 1 counts | 80 / 120 | 123 / 80 |
| Unique 18-byte slot indices per selector | all unique | all unique |
| Original packet bytes unaccounted | 0 | 0 |

**Important:** The 17-byte `group_selector` does not always correspond to the same global slot-index range across captures. In `test2`, selector 0 holds indices 0..39; in `cgm(1)` selector 0 holds indices 40..79. Therefore grouping **must use the actual selector**, not `index//40`.

The quantities in the prior demo UI (101, 1671 and 12868) were **not found at candidate count fields** in either packet. The demo screenshot is **not contemporaneous ground truth**, so item names/quantities should not be inferred from it.

## Source, validation and status

- `src/CgmC8Subtype02RecordLayout.php` decodes the five record forms into named **types and offsets**, with unknown fields explicitly labeled unknown. `decode()` returns the in-memory typed numeric records for future offline integration. `summarize()` outputs only counts, coordinate uniqueness and selector distribution; no raw record contents are logged.
- `tests/c8_record_layout_test.php`: **3087/3087 synthetic assertions passed locally**. Both historical response sizes and the two group-count layouts reproduced independently from synthetic field values. No captured network data is embedded in tests.
- `research/verify_phase33z1_captures.py`: original-file SHA256 verified, both original TCP streams reassembled and decoded in memory, PHP parser returned complete metadata for **2/2** C8/02 replies. The privacy-safe metadata results are in `research/CAPTURE_METADATA_RESULTS.txt`.
- This advances actual Garden record interpretation; it does **not** make CGM membership equivalent to PlayPark authentication, issue genuine LoginServer/GameServer sessions, or establish mobile Stage0/Stage1 compatibility.

## Next exact engineering step — Phase 3.3Z.2

Run `PyGhidra/CGM_Phase33Z1_Record_Consumers_Trace.py` on the same Windows executable and share `Desktop/CGM_Phase33Z1_Record_Consumers_Trace.txt`. The targeted functions (`0x00A69160`, `0x00A5EAD0`, `0x00793330`, `0x00793830`, `0x00A75B60`, `0x00A75B90`) help trace where the parsed 17-/18-byte records are **consumed, identified or displayed**. From those consumers and the original game's legitimate item metadata, identify actual plant/item IDs, quantity types and visible labels; do not guess from one screenshot. Only then build an account-data inventory projection and compare against same-session UI state.

Parallel critical path, unchanged: CGM-owned real Stage0/Stage1 processor and accepted official LoginServer → GameServer → Garden session. Current `CGM_ORIGINAL_ORIGIN=https://anlgarden.com` remains unchanged; no looping back to JPCG. Test Nox62033 is untouched; rollback Nox62030 must not be touched. JPCG production main/Render untouched.