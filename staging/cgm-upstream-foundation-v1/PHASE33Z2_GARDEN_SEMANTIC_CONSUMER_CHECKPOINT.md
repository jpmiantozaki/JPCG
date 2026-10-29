# CGM Auth-First — Phase 3.3Z.2: Garden Consumer Reality Check

Date: 2026-10-29. This checkpoint **continues from** completed Phase 3.3Z.1. It does not replace the established packet decoder or packet layout.

## Inputs actually reviewed

- The user's `CGM_Phase33Z1_Record_Consumers_Trace.txt` (678 lines), SHA-256 `a348f3eb01a1fd451b0e8508278ec644064bf3c6aedc443985f800202fe4bc59`.
- The user's exact Windows `Audition_unprotected_extracted.exe` in their previously uploaded ZIP, verified SHA-256 `a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911`, PE32 image base `0x00400000`.
- Previous Phase 33Y and 33Z.1 C8/02 captures, decompilation and structural checkpoints, **not repeated or modified**.

## Corrected classification of six functions

| Native function | Actual verified behavior | What earlier naming got wrong |
|---|---|---|
| `0x00A69160` | Sorts vectors containing 44-byte (`0x2C`) records by their first unsigned DWORD, recursively and through a heap-sort fallback | Not a semantic "collection merger" or item-name lookup |
| `0x00A5EAD0` | Creates a variable-length outgoing packet with command `0x0C` and sends it through `FUN_009E0050(..., 1)` (network connection slot 1); includes a time-dependent path | Not a verified Garden UI completion callback |
| `0x00793330` | Appends a single 4-byte value to a resizable vector | Not a specific Garden 17/18-byte record writer |
| `0x00793830` | Returns the element count for a 28-byte (`0x1C`) record array | Generic count helper, no category information |
| `0x00A75B60` | Resets/clears a tree-like container and its count | Generic collection operation, not semantic reset of a Garden item |
| `0x00A75B90` | Copies one 28-byte (`0x1C`) record into a vector, or grows the vector | Not a UI notification |

These corrections were directly supported by the newly uploaded Ghidra decompilations; don't elevate the script's speculative labels to factual names.

## What remains actually proven

- Original Garden TCP `20840` wire transport **fully decoded**: 61/61 captured frames across `test2.pcapng` and `cgm(1).pcapng` (previous Phase 33Y). Alternative transport uses one selector-selected XOR byte per packet body, not a per-byte table walk.
- Native C8 handler registered at `0x00A69670`, entered at `0x00A6A9C0`; `C8/02` case enters at `0x00A6AAE9`.
- The two long `C8/02 status=0` captures (5,357 and 5,622 plaintext bytes) have fully accounted-for structures: three counted arrays of 8-byte key/value pairs; nested 17-byte 80-slot, 10-byte extra-slot, and 3-byte records; a 200/203-element 18-byte array; five-byte trailer.
- 17-byte records form 40 + 40 complete 10x4 coordinate grids; 18-byte records use separate array selectors and 28-byte client storage cells.
- The REAL meanings of numeric item IDs, quantities and class flags **remain unverified**. Screenshots used for the old demo are not contemporaneous with the captures and must not be treated as ground truth.
- Mobile APK CGM ACTIVE eligibility and HTTP request forwarding are separate from genuine official account auth; no accepted PlayPark session or independent replacement session processor has been proven. Leave production `/session.php` and `CGM_ORIGINAL_ORIGIN=https://anlgarden.com` unchanged. Experimental Nox `62033` and rollback `62030` untouched.

## New exact-executable string anchors (verified offline)

Original `.rdata` contains Garden UI keys and names, verified by SHA-256 and VA:

- `CoupleGarden_1Page1Tree` at `0x00BD73FC`, referred to by a word embedded near `.text` VA `0x004AB68A`.
- `GARDEN_ADVANCE_ITEM_%d` at `0x00BD69BC`, word embedded near `0x004AF770`.
- `cg_seed_UP_%d` at `0x00BD8390`, near `0x004BCC16`.
- `CG_NOFLOWER` at `0x00BD6DE4`, near `0x004A94B6` and `0x008EA986`.
- `CG_DIEFLOWER` at `0x00BD6E18`, near `0x004A9526` and `0x008EAA0A`.
- `CoupleGardenShop` at `0x00BD8BC4`, 28 raw `.text` pointer matches.
- Additional verified strings include `CoupleGardenAppraisal`, `CoupleGardenExpansion`, `Garden_refill_success`, `Garden_Error`, `TEXTURE/gui/SecretGarden`, `garden_garade_%d`.

These are **promising UI/resource code anchors**, but a raw VA embedded in code is not proof of a function's meaning until Ghidra resolves and validates the instruction and data flow. They do not yet map item IDs to item names.

## New deliverables

1. `PyGhidra/CGM_Phase33Z2_Garden_Semantic_Xrefs.py`: runs inside the same Windows Audition CodeBrowser; validates exact binary C8 signatures and twelve strings; maps raw string pointer sites and nine C8-object field-displacement groups to containing functions and Ghidra instruction listing; optionally decompiles seven focused candidate entry points; saves `Desktop/CGM_Phase33Z2_Garden_Semantic_Xrefs.txt`. All operations static/read-only.
2. `research/pc_garden_anchor_audit.py`: standard-library-only metadata audit of the exact user-supplied executable, accepts `--exe` or `--zip`. Checks the SHA, PE imagebase and twelve resource keys, reports code pointer/field-displacement candidate VA locations without printing executable bytes, packet data or credentials.
3. `research/VERIFIED_ANCHOR_ADDRESSES.json`: offline source-address evidence to make the Ghidra probe reproducible, not client secrets or captured packets.

**Local validation completed:** Python compile/bytecode syntax and exact original EXE audit PASS, twelve Garden resource string VAs match, nine scalar-offset candidate groups recorded. Running the new Ghidra probe against the user's CodeBrowser remains pending. Do not claim semantic mapping until the new report arrives.

## Next exact target — Phase 3.3Z.3

Run `CGM_Phase33Z2_Garden_Semantic_Xrefs.py` in CodeBrowser (same Windows EXE). Share the resulting Desktop `CGM_Phase33Z2_Garden_Semantic_Xrefs.txt`. From its native function owners, follow item-definition lookup or UI formatting logic to establish ID-to-name and quantity field meaning, checking against an actual same-session Garden display/capture, then integrate a legitimate **read-only inventory projection** into the existing typed C8/02 parser.

Parallel login critical path remains separate: original APK → CGM membership → genuine accepted LoginServer/GameServer/Garden sessions. No new login UI, mock sessions or production changes.