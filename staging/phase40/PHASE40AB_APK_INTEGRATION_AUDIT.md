# Phase 40AB — Actual CGM APK integration boundary (read-only, 2026-10-21)

**Status:** Completed offline audit of archived signed CGM APK. No APK patches, Nox access, backend changes, real credentials, or live login tests.

## Verified native and Android package facts

- Archived signed APK SHA-256: `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`.
- ARM64 `libil2cpp.so` SHA-256: `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8` (matches earlier Ghidra/native mapping).
- ELF64 program-header analysis establishes original BL at `Login()+0x180`, RVA `0x6A8868`, targets `SendLogin()` at `0x6A80C0`. ARM64 instruction word `0x97fffe16`.
- APK includes ARM64 and ARMv7 IL2CPP binaries.
- Single `classes.dex` with 2,105 classes: UnityPlayerActivity exists; 16 `com/DeathToB/ANLGarden` resource/BuildConfig classes; **zero** `com/cgm` classes, and no known `CgmEmailLoginGate`, `UnityEmailGate`, `cgm-email-membership-v1` or `authfirst.php` DEX markers. This cannot rule out unnamed/obfuscated integrations, but establishes no known adapter.
- `Assembly-CSharp.dll` is registered in `ScriptingAssemblies.json` but not packaged as a standalone managed DLL. Game methods execute as native IL2CPP; adding Java listeners or scripts to APK assets does **not** intercept serialized Unity `Login()`.
- Existing localhost routing prefixes for all five mobile services remain in Unity metadata at `http://127.0.0.1:8091/`.
- The *installed* Nox62032 APK previously had different archive hash `44b89aa...`. Do not assume archived signed APK and installed APK are identical even if native library matches.

## Existing source code reused

Unpacked the complete prior `CGM_Phase26R_Implementation_Handoff.zip`; compiled and ran original C++17 `make test`. It passed tests for worker-to-Unity-thread dispatch contract, once-only ACTIVE, duplicate response, account change, timeout, cancellation and backend failure.

The C++ code **is host-tested only**. It has no real guest IL2CPP Login interception, Unity main-thread scheduler, Android HTTPS verification bridge, or signed staging APK. We did not restart Frida/Houdini experiments, which previously established only synthetic detours, not a working original CGM guest hook.

## Go/no-go decision

**GO**: Preserve the original `Login()`/`SendLogin()` entirely. Reuse the C++ policy with a demonstrated pre-submit owner-thread adapter (source-level if obtainable or an independently verified ARM64 guest native integration). Query email-only `authfirst.php` without transmitting a password. Treat all non-ACTIVE/expired/stale/errors as no submission. Retain original third-party and official upstream decisions.

**NO-GO**: No trivial Java listener, no replacement of the single BL in native `Login()`, no fake VIP error-to-success conversion, no simulated packet/session considered real Garden access. Existing CGM staging parser can check claimed identity at stage0 but that packet also contains credential material and does not remove ANLGarden entitlement. No live credential handling by unreviewed staging code.

**Next engineering dependency:** Matching Unity source/extension or a verified ARM64-native disposable Android test environment that can demonstrate actual pre-submit interception and safe resume, with the entire login state machine preserved. x86_64 Nox/Houdini ARM64 guest module attempts already failed to prove this. Do not touch rollback 62030 or historical 62031.

Offline executable audit bundle: `CGM_Phase40AB_APK_Integration_Audit.zip` (read-only Python standard-library APK/DEX/ELF structural audit, exact JSON output and written report), created separately for download.
