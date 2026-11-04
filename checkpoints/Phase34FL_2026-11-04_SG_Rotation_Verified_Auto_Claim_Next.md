# CGM Phase 34FL — Verified SG Rotation Checkpoint
Date: 2026-11-04 (Asia/Manila). **Next target: Phase 34FM — Auto Claim All Drops.**

This is a sanitized engineering checkpoint distilled from the CGM/JPCG project conversations, NOT a verbatim chat transcript or full backup of the user's working local Python source. Credentials, PINs, session artifacts, raw login packets and private user data are deliberately omitted.

## Confirmed system baseline
- Standalone Windows/Tkinter Couple Garden (CG) and Secret Garden (SG) Manager.
- Preserve official account authentication and second PIN; preserve local working source and rollback backups.
- CG: Manual Water All, Dust All, Fertilizer All, Harvest All, Plant All; native ACK-based pilots. Phase 34EY 0ms **additional fixed gap** after exact ACK was user-confirmed; zero gap does not mean parallel unacknowledged sends.
- SG: Fast action batches, one SG entry/tool selection per stage, per-target ACK checks, one server snapshot verification at end of each stage. Preserve current single-seed plan, 60-second monitoring and source-guarded approval of actually unlocked positions.
- CG and SG may be enabled together, but their writes must serialize through the shared busy/uncertain action lane.

## SG validated lifecycle and commands
| Exact SG bytes | Marker | Meaning |
|---|---|---|
| 00 00 01 | W | Needs water |
| 01 00 01 | F | Needs fertilizer |
| 01 01 02 | — | Growing |
| 01 01 03 | H | Ready harvest |
| 01 01 00 | D | Dead; removable via dead-specific harvest ACK |

- Refresh SG: C8/2C + C8/2D; SG exit C8/2E; menu/tool C8/39.
- Plant C8/32; Water C8/33; Fertilize C8/36.
- Harvest H: C8/38 index-and-coordinates ACK + C8/3A matching follow-up. Harvest D: status-zero matching C8/38 only in recorded clean dead-harvest capture, **no C8/3A required**. Position must still be verified empty in a fresh snapshot.
- Live results: 17/17 dead harvest ACK and `BATCH_ALL_HARVEST_POSITIONS_EMPTY_17`; Plant All batch 18 seeds and `BATCH_ALL_NEW_W_CELLS_AND_SEED_MINUS_18_CONFIRMED`; SG Fertilize All 20/20 ACK; full cycle stage F 20 positions verified.
- A completed one-click full cycle reported `FULL CYCLE: COMPLETE: stages=H,PLANT,W,F; all active stages ACKed and verified by fresh SG snapshots.`
- A **scheduled** SG cycle reported `FULL CYCLE: STAGE F VERIFIED: 7 position(s)` and `ROTATION: CYCLE VERIFIED: PLANT,W,F`. This confirms one live scheduled cycle, **not** continuous multi-hour reliability or separately verified SM/PM plans.

## SG schedules — one plan selected at a time
Only **one** of RM, SM, PM runs planting for a session; care/harvest operate on all eligible existing species. Historical time settings:
- RM Rosemary native ID 96: minutes HH:05/15/25/35/45/55, at/after second 20, >=10 min between starts.
- SM Spearmint ID 97: minutes HH:04/14/24/34/44/54, at/after second 20, >=25 min. Matching opportunities may effectively be 30 min apart; not guaranteed maximum.
- PM Peppermint ID 98: same minute pattern as SM, >=130 min.
The user's requested :00 instead of :20 adjustment **was discussed but not source/live verified**. Do not assert it is installed.
- Phase 34FI selects one plan. Phase 34FJ permits CG and SG master controls together but keeps actual-action busy protection.

## SG planting availability and UI
- Native SG potential board centers: A = [0,2,4,6,8], B = [1,3,5,7], 5x4 = 20 positions.
- C8/2C 20 positions and flag=1 alone do not prove all are unlocked. Previously a scheduled Plant All encountered short `c8 32 17 17` (meaning unverified) and paused without retries.
- Phase 34FK added session-bound manual unlock allowlist; Phase 34FL replaced flat checkbox list with a visual 5x4 map aligned with the official SG image, **user-confirmed** as matching the current tile arrangement.
- Only approved AND currently empty tiles can be Auto Planted. Approval expires on session change. An automatic unlock detector has NOT been proven.
- Upper SG panel displays herb stock from validated C8/2C; this is different from CG claimable drops.

## Selected release history
- 34BO: CG bulk Water/Dust ACK workflows; 34ET / 34EW / 34EY reduced fixed CG gap 75 -> 39 -> 20 -> 0ms, while maintaining ACK requirement; 34EU added CG manual bulk buttons.
- 34DM–34EPR: SG packet analysis, grids, care stages, manual one-shot controls, multi-action rearm.
- 34ES: SG Fast Water All; 34EX: Fast Fertilize All; 34EZ/34FA–34FC: Harvest including distinct dead-plant ACK; 34FD/34FE: Plant All and RM/SM/PM aliases.
- 34FF/34FG: One supervised full-cycle with a single initial approval; 34FH/34FI/34FJ: recurring schedules, single-seed plan, CG+SG coexistence; 34FK/34FL: approved tile checker with visual map.

## NEXT: Phase 34FM — Auto Claim All CG Drops
**Goal:** identify and collect all eligible CG drops (e.g. Hearts, Gift Boxes, Dens) across both pages, retaining the working CG and SG automations.

### Evidence / uncertainties
- CG displays page-specific drops and quantities; earlier captures observed C8/24 points/uncollected updates. This is **NOT proof C8/24 is a claim request**.
- Claim request opcode, target identity, reward/inventory effects, ACK and state change, page handling and safe batch behavior remain UNKNOWN.
- Do not confuse SG harvested-herb stock with CG droppable claim items.

### Safe implementation gates
1. **34FM-A Read-only drop census:** enumerate both pages, stable IDs, type, counts/locations, claimable versus unknown.
2. **34FM-B Official claim observation:** record ONE authorized official-client claim, before/after snapshot and matching network frames; map exact request, ACK, reward.
3. **34FM-C Claim One pilot:** exact current-session target guard, one request, one ACK, verified item disappearance or reward delta; stop on uncertainty.
4. **34FM-D Claim All:** ACK-gated, deduplicated, bounded per-page batch, no blind retry, verify each supported record and final snapshot.
5. **34FM-E Auto Claim option:** OFF by default, adjustable cadence, honor shared CG/SG busy lane; test collision/reconnect cases.

**Regression gates:** Preserve original login + PIN, CG zero-extra-gap ACK handlers, CG schedules, SG full cycle and one-seed schedule, SG unlock allowlist, source hash guard, backup/rollback, Python tests and Tkinter smoke.

## Backup scope and security
This public documentation commit is NOT the executable source backup or verbatim chat history. The definitive local working directory remains on the operator's Windows computer. Do not commit credentials or private raw game captures to this public repo. A separate user-downloadable reconstructed project checkpoint was generated in the conversation.
