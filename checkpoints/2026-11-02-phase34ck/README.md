# Phase 34CK — Continuous Monitoring and Newly Planted Flowers

**Checkpoint date:** 2026-11-02  
**Branch:** checkpoint/phase34bq-2026-11-02  
**Scope:** Sanitized research-only checkpoint. No credentials, network session tokens, private garden audit, screenshots, or installed source.

## User-confirmed live results
- Water All: 39/39 matching W+1 acknowledgments in a run at configured 75 ms minimum spacing.
- Dust All: 39/39 matching D+1 acknowledgments at configured 75 ms minimum spacing.
- Fertilizer All: 37/37 matching F+1 acknowledgments in screenshot; user reports two successful tests.
- Harvest Selected: single C8/0A removal verified.
- Harvest All: 12/12 matching removal acknowledgments.
- Plant Selected: C8/08-created flower visible in original Garden; a prior Plant Selected window reported an identity mismatch, not yet resolved.
- **Phase 34CK:** Water-needed [W] indicator changed while the Manager remained connected; no relogin or manual refresh required in the observed live test. Exact incoming state message not yet identified. Real-time D/F deterioration not separately demonstrated.
- **Phase 34CK:** Previously planted flowers are now observed with Level 1 display after relogin, rather than assumed missing. The user confirmed new plants appear in the Manager.

## Startup snapshot metadata
- Read-only startup audit: 70 raw records = 70 visible-model entries; 30 on Page 1, 40 on Page 2; no duplicate object IDs or anchors.
- Growth levels: Level 1 = 4, Level 2 = 41, Level 4 = 25; no unknown growth in that audit.
- One known planted flower: Blue Lily seed ID 264, object 40, Page 1 at grid (8,5), growth 1; a different previously selected Level 1 flower was object 47.
- Phase 34CI addressed sorted UI row / raw record selection mismatch. Do not infer object identity from visible name or list index.

## Periodic Garden packets
- TCP keep-alive probes ~10 seconds apart are transport liveness checks.
- Two different six-byte application packets ~30 seconds apart were previously decoded as the same C8/C8 command; likely periodic app maintenance, but purpose is unverified.
- No reason to add those messages to force W updates: user-confirmed W updates already occur without manual refresh. Connection liveness does not prove current state.

## Preserve
- Keep existing login, Second PIN, GardenCipher, receiver, page sorting, and native action senders.
- One care/harvest/plant **run** per Garden login, explicit confirmation, ACK-gated requests, manual Stop, no retries.
- Do not enable Auto Plant: validated plantable tile geometry and one-plant ACK identity matching are still incomplete.
- Unknown growth label [Lv ?] was requested to be restored, but confirmation of installation is not available. Do not claim it is live installed.

## Next tasks
1. Archive the operator's real Windows working folder locally. This GitHub note is NOT a source or binary backup.
2. Observe D and F deterioration while Manager remains connected and identify inbound update that caused W deterioration.
3. Validate native selected-row identity and investigate Plant Selected false ACK mismatch before Auto Plant.
4. Continue read-only SG slot mapping investigation without changing successful Couple Garden automation.

## Scope
A separate ChatGPT checkpoint ZIP contains user screenshot and audit plus selected archived patch packages; these files are intentionally excluded from the public GitHub branch. The actual live-tested Windows working directory must be backed up on the operator's PC.
