# Phase 40AM — Read-only installed CGM Unity callback parity audit

**Status (2026-10-22):** User-directed NEXT disposable Nox62032 read-only verification package prepared, with offline tests PASS. **No emulator has been accessed and no installed CGM APK has been modified in this phase.** Runtime device comparison pending user.

## Why this checkpoint is needed

Phase40AF proved one-byte reversible listener state on **archived** CGM APK SHA256 `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`. Prior checkpoint indicates a potentially different signed APK installed on Nox62032. Before touching executable Unity callback layout, determine whether the target installed CGM client's serialized Unity scene matches the archive.

Phase40AJ: original Phase40AH JNI on **isolated test app** Android 27/27 synthetic assertions PASS.
Phase40AK: isolated app real HTTPS synthetic email returned INACTIVE; native deny/cancel/fail-closed PASS. The actual original CGM callback and ACTIVE membership flow remain untested.

## New artifact and reproducibility

Conversation file: `CGM_Phase40AM_Installed_Client_Parity.zip`, SHA256 `6b3b28670c73ddbc122c3c80d9b7e70b0ba853a5bfbcc5d51c885080f284f672`. Contents:
- Unmodified `Phase40AF/prove_confirm_listener.py` reused as the Unity scene reader (not its patch-writing routine).
- Pure Python raw-LZ4 decoder bundled, so no pip/network/Gradle/NDK needed for reading UnityFS.
- `scripts/phase40am_compare.py`: read-only report of archived vs installed APK SHA256, UnityFS level1 scene SHA256, CONFIRM (PathID4300)/original Login target (PathID4610) and call state, outer yellow LOGIN PathID4164, `classes.dex`, `global-metadata.dat` and ARM64 `libil2cpp.so` byte hashes, and gate loader references.
- `scripts/run_phase40am_readonly.ps1`: 1 command, hard-coded ADB `127.0.0.1:62032`, uses ONLY `get-state`, `shell pm path com.DeathToB.ANLGarden`, `adb pull` to make a local archival copy. Does not install, start, patch, enter credentials, connect to other Nox ports, or touch servers. Stops if original client not installed under expected package.

On the actual archived 111 MB input, parser reported EXACT_ARCHIVED_UNITY_SCENE_MATCH, original scene SHA256 `aba884bc473798fdd844cafe66c2e6ba8894f69dbc07a9f241216a9f7031c353`, 5,489 objects, Login() Path4300 -> Path4610, state=2, all code+metadata hashes matched archived, no gate in DEX/ARM64 APK. Offline packaged ZIP integrity PASS.

## One-step user invocation

Extract ZIP into `D:\CGMTEST\CGM_Phase40AM_Installed_Client_Parity`. Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AM_Installed_Client_Parity\scripts\run_phase40am_readonly.ps1'
```

Send summary output or `results\Phase40AM_Installed_Scene_Parity.json` to determine whether exact installed scene matches archived. Do not upload full game APK unless needed. Read-only comparison **cannot** establish the missing executable membership callback. No permitted Unity source or proven native callback insertion path established. Preserve original login and all ANLGarden/PlayPark/Garden server responses. Never modify protected Nox62030/62031.


## User Nox62032 result — Phase40AM PASS (2026-10-22)

User ran `scripts/run_phase40am_readonly.ps1`. ADB read-only pull of the installed `com.DeathToB.ANLGarden` base.apk succeeded. Reported installed APK SHA256:
`44b89aa390734b2d05705a20620d6175e298ed790d88734d245e87d98ac00944`.

```
classification: EXACT_ARCHIVED_UNITY_SCENE_MATCH
scene_matches_archived: True
classes_dex_matches_archived: False
metadata_matches_archived: True
native_matches_archived: True
gate_native_library_packaged: False
gate_loader_literal_in_dex: False
```

Interpretation: the inner CONFIRM serialized binding and the underlying archived scene, native IL2CPP, metadata are byte-identical. Whole APK and Android `classes.dex` differ. **Do not infer the DEX change's nature until comparing class/method identifiers/code.** No CGM JNI loader or native library is proven inside this original installed client. No on-device mutation occurred in this audit.

Next: offline Phase40AN DEX structural delta using the already-downloaded Nox62032 base.apk local snapshot.