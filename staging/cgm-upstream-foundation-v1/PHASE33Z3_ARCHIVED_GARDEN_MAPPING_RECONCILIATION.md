# CGM Auth-First / Garden — Phase 3.3Z.3 archive reconciliation

Date: 2026-10-29 (Asia/Manila). **Important: this phase restores prior proven semantic mappings, it does NOT discover another cipher or implement official authentication.**

## Why this checkpoint exists

The user supplied `CGM_Phase33Z1_Record_Consumers_Trace(2).txt` following `CGM_Phase33Z1_Record_Consumers_Trace.txt`. A byte-for-byte local comparison proved the two files were **identical** (SHA-256 `a348f3eb01a1fd451b0e8508278ec644064bf3c6aedc443985f800202fe4bc59`). No additional PyGhidra work is justified on this duplicate.

The user's archived CGM Garden Manager reports **Phase 4.7, 4.9, 4.10, 4.24, 4.25, 4.26, 4.28** already established semantic field mappings and even a read-only official-PC-client Garden observer. These reports were in the user's ChatGPT file Library, not previously incorporated into the new Phase 3.3Z.1 source. This phase reconciles them.

## Reliable archived mappings now in staging PHP

`src/CgmC8Subtype02RecordLayout.php` now preserves original output keys for compatibility and adds:

- **Fixed initial 21 bytes** after C8/02 status: `u32 garden_points`, `u32 water_count`, `u8 water_tank_candidate`, `u32 fertilizer_count`, `u32 vitamin_count`, `u32 garden_background_id_candidate`. The last two candidate labels remain qualified by original evidence.
- **Three counted 8-byte catalog arrays**: `item_id:u32 LE`, `quantity:u32 LE`; category labels `seeds`, `accessories`, `management` from Phase 4.25 (NOT harvest drops). Old `pairs8` and `field0_u32/field4_u32` aliases are preserved.
- **Each 17-byte planted flower**: at 0 instance ID (u8); 1..4 seed catalog ID (u32); 5 page selector; 6 X; 7 Y; 8 water state; 9 clean/dust state; 10 fertilizer state; 11 growth level; 12 growth points; 13 vitamin state; 14 soil state; 15 unmapped movement-related byte; 16 moving-state **candidate**. Old `unknown_tail_u32` is retained solely as a deprecated compatibility alias, NOT one semantic integer.
- **Each 18-byte independent state/position record**: 0 slot index; 1..4 garden-group index; 5..8 position axis 1 candidate; 9..12 position axis 2 candidate; 13 status code (0,1,other are distinguished, but their gameplay meanings are NOT proven); 14..17 unresolved auxiliary value.
- `summarize()` returns counts and original grid geometry only. No actual item IDs or account-derived scalar values are printed.
- `harvest_drops_status=NOT_DECODED`, `secret_garden_status=NOT_SUPPLIED`, `authenticated_session_proven=false`. The source is read-only.

Previous Phase 4.9 mapped **Garden Grade** from Garden points separately, with thresholds `[0,20000,40000,60000,80000,100000,140000,180000]` from `gardenrating.txt`; this is NOT the account Couple/ring level.

Previous Phase 4.10 recovered the archived `couplegarden.txt` catalog with **496 unique item IDs**. Names and archival prices are client assets, not a live Shop offer or confirmed inventory.

## Prior real evidence, not repeated

- Phase 4.24 recovered the same single-key-per-packet Garden port `20840` decoder. Two original recordings produced 80 planted flowers, 40 per page, and respective seed/accessory/management counts `6/27/4` (PC) and `4/30/7` (APK).
- Phase 4.25 corrected the meaning of `CG Items`: these are *uncollected harvest rewards* (Hearts/Gifts/Den), and are **NOT** the same as the catalog accessories or management collections. Harvest-drop fields remain unverified.
- Phase 4.26 built a passive official PC client observer; Phase 4.28 (Garden Manager v0.9.6) documented a user screenshot of the **initial live C8/00 -> C8/02 Garden decode**. Continuous follow mode was implemented and unit-tested but **post-purchase inventory updates and runtime follow behavior remained unverified on Windows**. This uses the official client's own authenticated session, not a separate CGM/PlayPark session.
- Exact Windows PE: `Audition_unprotected_extracted.exe` SHA256 `a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911`, base `00400000`. The Android `libil2cpp` exported class declaration stubs establish field *names* but don't supply working server authentication.

## Local and CI tests

- Original Phase3.3Z.1 regression suite **3,087/3,087** synthetic assertions PASS after the change.
- New `tests/c8_archive_semantics_test.php` **56/56** additional synthetic assertions PASS, covering two exact archival frame-size-shaped fixtures, distinct offsets +13..+16, category counts, scalar order, 18-byte state aliases, and preventing fabricated harvest/Secret Garden/authentication labels.
- There is **no actual archived PCAP** in this GitHub commit, and these new tests alone do not constitute independent capture replay. Earlier Phase 4.24 archive describes real-capture verification. No user credentials, tokens, raw captures, private Garden snapshots or proprietary binary assets are committed.
- The earlier Phase33Y transport code, packet boundaries and Phase33Z structure parser are unchanged.

## Next priority: stop duplicating Garden semantics

**The next missing capability is legitimate independent official-login/session acceptance, not Garden field names.** The original APK still routes its `/authenticate.php` and `/session.php` through the independent CGM HTTP relay but the original intermediary's VIP gate rejects non-VIP sessions. CGM ACTIVE membership is separate from official PlayPark authentication and does not grant an accepted original intermediary session.

Resume the upstream/client boundary using existing Phase3.0 and Phase3.3W evidence: enumerate the authentic Stage0/Stage1 and official login/game acceptance responses, document what an **authorized official session provider** must return, and verify the original APK's handoff with real permitted game acceptance. Avoid duplicating `garden_tool.py`, PC/Winsock/cipher decompilation, and Garden catalog UI scans. Do not change `CGM_ORIGINAL_ORIGIN` to JPCG, because that creates an upstream loop.

If the actual goal is **only live read-only Garden data** rather than a separate mobile login, the already-built Phase4.28 official-client observer is the more concrete existing route, pending Windows follow-mode validation.

JPCG production `main`, Render, original APK, experimental Nox62033 and rollback Nox62030 are untouched.
