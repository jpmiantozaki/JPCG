# Phase 34BQ — CGM Direct Garden Desktop Checkpoint
Date: 2026-11-02 (Asia/Manila)
Last live-verified build: Phase 34BO. Current SG research: Phase 34BQ.

## Verified working product
- Standalone Windows Python/Tkinter Garden Manager with existing official login and second PIN, direct Garden TCP 20840 session, viewer, and native care actions.
- Phase 34BO Water All: CONFIRMED 39/39 plants, W+1 ACK per plant.
- Phase 34BO Dust All: CONFIRMED 39/39 plants, D+1 ACK per plant.
- These are 78 successful acknowledgments across separate tests, not proof of 78 unique plants or 3-second completion.
- Prior Phase 34BK Water All confirmed 40/40 and Phase 34BN Clean All confirmed 40/40 on populated Page 2.
- 80 is a maximum target count (up to 40 per page); 39/39 is valid when 39 plants are eligible.
- 75 ms is configured minimum spacing, not guaranteed completion time. Each request waits for the matching ACK. No automatic retries; manual Stop and single-session guard remain.
- Approval phrases: WATER80 for Water All; DUST80 for Dust All (formerly Clean All).

## Confirmed protocol / care state
- Native Water state update C8/0B, verified W:3->4 and W:4->5.
- Native Clean/Dust update C8/0F, verified D:3->4 and D:4->5.
- Display W cue at raw water 1..4; D cue at raw dust 1..4; 5 suppresses cue; 0 excluded pending interpretation.
- One request per eligible plant per batch; exact +1 ACK required before next request; unexpected ACK halts.
- Fertilizer [F] cue is visible, but a live native fertilizer request is not yet established.

## Phase chronology
34BA Clean Selected; 34BC three-plant pilot; 34BD ten-plant; 34BE twenty-plant and CRLF fix; 34BF forty-plant; 34BG eighty-target; 34BH/BI W health display; 34BJ W=1..3 one-shot test; 34BK incremental Water All; 34BL D health display; 34BM D=1..3 one-shot; 34BN incremental Clean All; 34BO 75 ms configured Water All and Dust All with 39/39 live proof; 34BQ SG investigation.

## Secret Garden research (observations vs inference)
- Same Garden server TCP port 20840 as Couple Garden; no observed redirect.
- Connection with client-side port 54932 carried real traffic; 55480 appeared idle / keep-alives.
- Around 4.30s client 18 B; server 203 B then 9 B.
- Around 19.12s client 9 B; server 10 B. Around 19.17s client 6 B; server 7 B.
- Interpreting these messages as SG entry, readiness and CG return is provisional; no opcodes were decoded.
- EXE resources: script/secretgarden.slk; SecretGardenMenu, SecretGardenShop, SecretGardenHerbShop, SecretGardenHangariMix, SecretGardenChatting; SecretGarden/Loading; SG_MAXHURB; SG_FAILED_ITEM_IS_FULL; sg_bt_cgstraight.
- SG slot issue may involve distinct SG serialization or entry-state sequencing, not proven.
- Next: inspect redacted bidirectional stream, decode framing, inspect 203-byte reply and compare CG vs SG state/slot mapping. Never force a CG 17-byte record parser onto unverified SG payloads.

## Priorities / guardrails
1. Back up the operator's Windows working folder locally before further edits.
2. Investigate Fertilizer Selected via one controlled live request and acknowledgment, then consider Fertilizer All.
3. Investigate Secret Garden slots using offline read-only parsing.
4. Decode Harvest and Planting actions and confirmations separately.
5. Keep the 34BO working build unchanged until rollback is verified.

## Backup scope and limitations
This GitHub commit is a SANITIZED HANDOFF, not an installed source backup. The user's live-tested working folder is on their Windows computer and has not been imported here. Separate conversation recovery ZIP and TXT contain archived patch files, test evidence and a manifest. Those binary files are NOT included in this public repo branch.
Local root: C:\Users\Admin\Desktop\CGGTEST\TEST\working
Local command (PowerShell, after closing Garden Manager):
  $root = 'C:\Users\Admin\Desktop\CGGTEST\TEST'
  $stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
  Compress-Archive -LiteralPath "$root\working" -DestinationPath "$root\CGM_PHASE34BO_WORKING_BACKUP_$stamp.zip" -CompressionLevel Optimal
Never commit account credentials, PINs, session tokens or unredacted captures to the public repository.
