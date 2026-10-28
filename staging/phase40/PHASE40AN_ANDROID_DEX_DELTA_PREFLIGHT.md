# Phase 40AN — read-only local Android DEX difference analysis

**Date:** 2026-10-22. **Status:** A byte-for-byte Unity scene and ARM64 IL2CPP native match was confirmed on disposable Nox62032 by Phase40AM, while classes.dex differs. Baseline-generated DEX comparator and offline tests PASSED. Actual user installed DEX structural comparison PENDING.

## Grounded input

- Archived trusted reference APK SHA256 `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`.
- Archived `classes.dex` SHA256 `1719e3f09ae56a531116634967fd7106de384e3d282fa212174ed5cf46844140`.
- Nox62032 installed base.apk local snapshot SHA256 from user's Phase40AM run: `44b89aa390734b2d05705a20620d6175e298ed790d88734d245e87d98ac00944`.
- Phase40AM results: `EXACT_ARCHIVED_UNITY_SCENE_MATCH`, metadata and native identical, classes.dex different, `libcgm_authfirst_gate.so` not present, CGM loader literal absent in DEX.
- Phase40AJ separate app 27/27 recovered native JNI assertions PASS on Android, and Phase40AK separate app synthetic HTTPS INACTIVE with fail-closed PASS. **Neither proves a real CGM Unity callback.**

## New diagnostic package

`CGM_Phase40AN_Android_DEX_Delta.zip` SHA256 `ef4d1e1868b0cd32f3c04ec189db8700a640a6ed43d23bf758e7df45314bd74a`, 4 source/data files, ZIP CRC PASS.

- Contains non-executable reference fingerprints (class descriptors, method signature identifiers, Dalvik instruction hashes) derived from user's archived APK. Does **not** ship the actual reference classes.dex binary.
- Dependency-free Python parser compares DEX table counts, classes, implemented method names/code hashes and selected loader-related string presence; reports summary and full JSON to local results folder.
- PowerShell wrapper verifies only the previous Phase40AM *local copy* SHA256 and calls `py`. There are **zero** ADB, emulator, install, network, username, native hook, APK patch, or Garden operations.
- `DEX_BINARY_DIFF_NO_CODE_HASH_DIFF` means only compared signature/instruction hashes match, not full program behavior; excluded data includes annotations, exception handlers and resources, so this is not equivalent to binary equivalence.

## Offline tests

- Parsed archived baseline: 2,105 classes, 16,205 implemented methods, 21,133 method IDs; baseline hash matches Phase40AM.
- Identical DEX: `EXACT_DEX_MATCH` PASS.
- Header-only DEX difference: `DEX_BINARY_DIFF_NO_CODE_HASH_DIFF` PASS.
- Synthetic method-instruction byte difference: `DEX_METHOD_CODE_DIFFERENCES` PASS.
- ZIP integrity: PASS.

**Next user command:** extract package alongside `D:\CGMTEST\CGM_Phase40AM_Installed_Client_Parity` then run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AN_Android_DEX_Delta\scripts\run_phase40an_readonly.ps1'
```

Expected output classification and method/class counts; report `D:\CGMTEST\CGM_Phase40AN_Android_DEX_Delta\results\Phase40AN_Installed_DEX_Delta.json`. Read-only uses existing local `...Phase40AM...\collected\Nox62032_CGM_base_readonly.apk`, does not pull another copy.

**Decision boundary:** The purpose is to determine whether DEX changes implicate the Unity Android loader or are unrelated. Even if no DEX methods changed, the original CGM APK still has no verified executable CGM gate callback. Never disable the original Unity CONFIRM listener 4300->Login() 4610 without an authorized, fully functioning replacement; preserve upstream server decisions. Protect Nox62030 and 62031.

## User Windows result — Phase40AN comparison COMPLETE (2026-10-22)

User ran `scripts/run_phase40an_readonly.ps1` on local Phase40AM collected APK. Reported:
```text
installed_base_apk_sha256: 44B89AA390734B2D05705A20620D6175E298ED790D88734D245E87D98AC00944
archived_dex_sha256: 1719e3f09ae56a531116634967fd7106de384e3d282fa212174ed5cf46844140
installed_dex_sha256: af5058ac45a5630d662fdf2413d501c771b9972043011473a36c5549fe68b312
classification: DEX_METHOD_CODE_DIFFERENCES
classes_added: 0
classes_removed: 0
implemented_methods_added: 0
implemented_methods_removed: 0
methods_with_different_instruction_hash: 1
Landroidx/core/graphics/PaintCompat;->hasGlyph(Landroid/graphics/Paint;Ljava/lang/String;)Z
potentially_relevant_changed_methods: 0
Loader literal changed: [none reported]
```
**Outcome:** In the comparator's class/method/instruction coverage, only the AndroidX graphics glyph-checking method's instructions differed. No added/removed classes or implemented methods, no detected loader-related method differences or changed gate-loader literal. This removes the specific concern that the installed DEX diverges substantially in its Java loader entrypoints. This does not prove overall bytecode/metadata equivalence or that a new CGM native gate is executable from the original Unity CONFIRM event. Phase40AF CONFIRM Path4300->Login() Path4610 remains intact.

**Next:** Stop repeating offline DEX similarity tests. Prioritize a verified executable Unity-owner-thread callback and original Login() continuation pathway; source-level integration if matching original Unity project is available, otherwise an explicitly validated nonproduction extension route is still required. Never turn off CONFIRM before replacement works.
