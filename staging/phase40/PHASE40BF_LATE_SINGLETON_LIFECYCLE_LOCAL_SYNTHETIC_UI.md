# Phase 40BF/BG — live singleton reference observed at credential UI

**User-confirmed on-device milestone (2026-10-23).** The initial Phase 40BF wrapper stopped safely before building because of an unintended parent results-directory guard. Phase 40BG corrected that script collision without deleting prior results, and user ran the revised one-command Windows/Nox62033 workflow.

## User-supplied confirmed console and screenshot

- Existing disposable Phase40AW SHA256 `9033A6BCFAE4CF39B8A1D1C0368FB3BC6CF0D8235AA8846DECEECA207691E39E` verified before update.
- New test-signed Phase40BF APK built, installed, exact SHA256 `C536D7E6F04D66FA4878E9D24F0788B6E4C62DD3B18DCC0329CCB50E6E444C78` reported.
- User followed `START`, waited until initial yellow LOGIN was visible, typed `READY`, clicked original **outer** yellow LOGIN only, then `CLICKED`. No username/password or inner CONFIRM submitted in this test.
- Local-only synthetic `/authenticate.php` configuration fetches: **1**; local no-data TCP listener accepts: **1**.
- Native read-only sampler yielded **4 after-click snapshots**; main report classification `POST_CLICK_SINGLETON_NON_NULL_SNAPSHOT`.
- Actual Nox screenshot shows original colorful Audition Next Gen screen with **Login ID, Password, CONFIRM and CANCEL** UI after the initial yellow LOGIN stage.
- Run printed `Cleanup attempted: temporary local service + Nox62033 mappings only. Test APK remains installed; original protected emulators untouched.`
- Generated files **still on user's Windows PC and not yet uploaded**:
  - `D:\CGMTEST\CGM_Phase40BG_Phase40BF_Result_Dir_Fix\results\Phase40BF_20261023_014141\Phase40BF_Runtime_Report.json`
  - `...\Phase40BF_Native_Log.txt`
  - `...\Phase40BF_Credential_Screen_No_Entry.png`

## Meaning and evidence limits

User's output demonstrates native IL2CPP probing observed a **non-null static LoginServerManager.Instance reference at least once after the first-stage outer LOGIN**, in the actual modified original CGM Android Unity process, with credential UI visible. It does NOT prove the reference was NULL immediately before the click, stable across all snapshots, GC-pinned, or safe for arbitrary use from a background thread. Earlier Phase40AW 12/24s startup-only snapshots were NULL, but did not control for loading/click timing. Use actual Phase40BF timestamped JSON and native log for temporal transition and stability, **without further game interaction**.

No original inner CONFIRM PathID4300 -> `Login()` component PathID4610 was disabled or intercepted. No original `Login()` or `SendLogin()` invocation by test. No CGM ACTIVE membership pre-submit authorization, PlayPark/game authentication, independent ANLGarden VIP or Garden session accepted. Synthetic local config and TCP listener were deliberately unauthenticated/no-packet and are not production session substitutes.

## Next recommended checkpoint: Phase40BH offline evidence audit

Before another APK build, read `Phase40BF_Runtime_Report.json` and `Phase40BF_Native_Log.txt` already generated, and specifically inspect sample statuses/timestamps before and after the manual click, how many non-null vs null, and whether reference remained present. Do not publish pointers, credentials, device IDs, or raw query strings. Then design narrowly-scoped validation of scene-component identity, object-lifetime and Unity owner-thread safety; no direct invocation of Login() or replacement of server decisions merely because a pointer exists.
