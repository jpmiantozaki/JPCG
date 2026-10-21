# Phase 40AQ — Original CGM APK content-preserving, UNSIGNED staging overlay

**Status (2026-10-22):** After user completed Phase40AP staged loader build PASS, prepared a one-command OFFLINE/UNSIGNED content merge and validated source-copy integrity; **not installed, signed, run, or integrated into Unity**.

## User's preceding Phase40AP Windows result

```text
=== PHASE40AP ORIGINAL UNITY PRELOADER STATIC ROUTE PASS ===
Installed source APK hash: 44b89aa390734b2d05705a20620d6175e298ed790d88734d245e87d98ac00944
UnityPlayer constructor calls preloadJavaPlugins: YES
preloadJavaPlugins calls Class.forName: YES
Expected class absent from original DEX: YES
=== PHASE40AP STATIC LOADER PARTS BUILD PASS ===
DEX SHA256: A055BCEC754D83004049B5640788B99FCC8F47887D632D1E4FABF1E0EB84873E
ELF SHA256: 037A6A9CA3266E4D4AD47D2B4EE53DDD3D71C1DF6388793BBEA11D04DF8A0A36
NO original CGM APK was changed; NO emulator accessed or app installed.
```

**Interpretation:** Java candidate preloader DEX plus original native gate library have been built and statically verified. The candidate loading route is backed by original UnityPlayer DEX. The original game process has NOT loaded this class/library, and Unity CONFIRM PathID4300 still directly invokes original Login() on component 4610.

## New Phase40AQ test kit

Conversation artifact `CGM_Phase40AQ_Unsigned_Staging_Merge.zip`, SHA256 `cf83ae811a4196b6cc4a66f91b39d377fdc354bd6be5c564789d6d1362a4759c`.

Contains Python stdlib staging script, one-command Windows PowerShell wrapper, README, and synthetic regression test. The wrapper pins SHA of user Nox62032 *local copy* (`44B89AA3...`) and Phase40AP DEX and native ELF before creating a new **unsigned** staging package in its own directory. It adds only `classes2.dex` and `lib/arm64-v8a/libcgm_authfirst_gate.so`, removes obsolete signature metadata (V2 signature block lost when rewriting), retains all original ZIP-entry payload bytes and verifies SHA parity. It never signs or installs the output. Existing source APK remains untouched.

Rehearsal validations:
- Synthetic archive merge parity/old signature-removal/overwrite guard PASS.
- Real archived 116MB CGM APK full-entry preservation rehearsal (with placeholder test overlay bytes) PASS: 167 original entries preserved byte-for-byte, 3 prior signature metadata entries excluded, original input SHA `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0` unchanged.
- ZIP CRC of the new kit PASS.
- **User Windows Phase40AQ merge NOT YET RUN**.

## One-command user test

Extract to `D:\CGMTEST\CGM_Phase40AQ_Unsigned_Staging_Merge`, then:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AQ_Unsigned_Staging_Merge\scripts\run_phase40aq_offline.ps1'
```

Expected `OFFLINE_UNSIGNED_STAGING_CONTENT_PARITY_PASS` and JSON report at `...\staging-only\Phase40AQ_Unsigned_Staging_Report.json`.

**The generated `Phase40AQ_CGM_Preloader_UNSIGNED.apk` must NOT be installed:** it is unsigned, package ID matches original game, a different signing key would be incompatible with a same-package update, and no permitted runtime integration/Unity original-login continuation has been proven. No Nox62030, 62031, 62032 changes, no CGM/PlayPark/Garden server requests.

Next GO/NO-GO requires a separate, user-approved reversible emulator deployment plan and a verified authorized onClick or Unity-side callback interception mechanism. Do not disable original CONFIRM persistent listener without an executable working replacement. CGM ACTIVE alone does not substitute for ANLGarden or official PlayPark session authorization.

## User Windows result — Phase40AQ offline merge PASSED (2026-10-22)

User ran `run_phase40aq_offline.ps1` and reported:
- `=== PHASE40AQ OFFLINE MERGE CONTENT PARITY PASS ===`
- 167 original non-signature ZIP payloads preserved identically
- 3 obsolete signature entries removed
- Unsigned output SHA256 `9d2a429456ae8353be4a70d2b8c037c08c4e2c65e2f10c7b4df182d9e617af1d`
- JSON report saved in `staging-only/Phase40AQ_Unsigned_Staging_Report.json`
- Not signed/installable, original game unmodified, no runtime bridge test

Next: Phase40AR read-only-input signing/certificate comparison on a separate local staging copy only. Never install on occupied Nox62032 or protected 62030/62031; the original signing identity and backup/rollback data must remain safe.
