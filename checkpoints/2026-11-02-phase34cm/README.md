# Phase 34CM — Realtime W/D/F Confirmed; Repeated Manual Testing Requested

Date: 2026-11-02. Sanitized research checkpoint only; no local installed code or user garden data included.

## Confirmed live results
- Water All 39/39 (W+1 ACK per plant).
- Dust All 39/39 (D+1 ACK per plant).
- Fertilizer All 37/37 (F+1 ACK per plant); user reported testing twice.
- Harvest All 12/12 (native C8/0A plant removal ACK).
- Plant Selected: user confirmed newly created Blue Lily in official Garden; Plant Selected's matching-identity ACK check remains imperfect.
- **All three W, D and F need indicators updated while the Garden Manager remained connected**. No relogin was necessary in those live tests.
- Snapshot audit: 70 plant records (30 Page 1, 40 Page 2), including object 40 Blue Lily seed 264 at Page 1 (8,5) with growth level 1. User later observed new flowers appearing at Level 1 after fresh login.
- Phase34CI addressed a visible-row sorting and original-record identity mapping bug; continue careful validation.
- TCP keep-alives and recurring C8/C8 application packets were observed; no evidence these are needed to refresh W/D/F, which already worked in-session.

## New request: Repeat testing without a forced relog
The user asks to remove the **one-action-run-per-login lock**, allowing multiple *manual* action runs in the same authenticated Garden session, including tests with other accounts.

Do NOT interpret this as permission to remove all safety controls. Preserve:
1. Explicit manual action confirmation.
2. Valid authenticated session and fresh server state.
3. Only one in-flight batch/action globally.
4. Matching per-object server ACK before advancing.
5. Manual Stop and no automatic retry.
6. Stop and require state reconciliation on timeout/uncertain-send instead of silently unlocking.
7. Rebuild target eligibility from current snapshot before each new run.

Guard code is distributed among phase34ax_live_pilot.py, phase34bc_live_clean_pilot.py, phase34by_fertilizer_all.py, phase34ca_harvest_one.py, phase34cb_harvest_all.py, phase34ce_plant_selected.py, and phase34bw_fertilizer_one.py; inspect exact installed source before patching. Persistent flags include `_cgm_phase34ca_global_attempted`, `_cgm_phase34bc_attempted`, and action-specific attempted flags. Use a unified active-operation gate released after confirmed completion; do not blindly reset legacy flags in active or ambiguous sessions.

**Status:** A safe patch to relax the per-login lock has NOT yet been applied or live tested. An offline source inventory and local working-folder backup script were delivered. Do not claim the installed Manager has changed.

## Backup scope
Conversation ZIP: CGM_Phase34CM_RealTime_WDF_Backup_and_Action_Guard_Review.zip, with checkpoint TXT, historical patch archives, a full working-folder backup PowerShell script, and read-only guard inventory script. The live Windows `working` folder must be backed up on the operator's machine, separately. Avoid credentials/PINs/session secrets in public GitHub.
