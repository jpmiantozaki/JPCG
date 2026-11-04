# Phase 40AD — Uploaded Legacy BASE APK vs archived CGM APK

**Status:** Offline, read-only comparison completed 2026-10-21. No live requests, Nox access, executable launch, authentication tests, backend changes, or APK patching.

## Identified files

| File | SHA-256 | Bytes |
|---|---|---:|
| Uploaded `LegacyAPK.apk` | `b0ca65507c2cede6e9fafb7bc9597a354fc97056d0eadcf244c277815b5d8611` | 137,619,712 |
| Archived `CGM-62031-Local-SIGNED.apk` | `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0` | 116,133,295 |

The total archive sizes differ due to signing, compression and Android resource packaging. Both ZIP archive integrity checks passed. The currently installed disposable Nox62032 build has another historical APK hash; do **not** assume the archived CGM APK is byte-identical to the installed target.

## Byte-for-byte identical game components

- `classes.dex` (exact hash match).
- `assets/bin/Data/data.unity3d` (exact hash match).
- `assets/bin/Data/ScriptingAssemblies.json` (exact hash match).
- `lib/arm64-v8a/libil2cpp.so` and `lib/armeabi-v7a/libil2cpp.so` (exact hash matches).
- Corresponding `libunity.so` and `libmain.so` native libraries (exact hash matches).
- Established ARM64 native library SHA-256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`.

Thus the prior Ghidra mappings for `Login()`, `SendLogin()`, `RelayServerData`, VIP handling, official LoginServer result parsing and Garden client are reusable. No separate legacy client-native session provider was discovered.

## Actual metadata difference

Both `assets/bin/Data/Managed/Metadata/global-metadata.dat` are exactly 4,906,768 bytes. Only **80 byte positions differ**, confined to one neighborhood of **five mobile backend URL prefixes**.

Legacy mobile calls go to `https://anlgarden.com/` for:
`analytics.php?data1=`, `authenticate.php?version=`, `redeemvip.php?data=`, `session.php?data=`, `verify.php?data=`.

The archived CGM copy substitutes `http://127.0.0.1:8091/` for those same five route names. Original Garden endpoint strings under `au.moveongame.com/appapi/gardenapi` remain in both builds.

**Interpretation:** The supplied legacy client has the same inherited compiled login/relay/VIP implementation as the compared CGM build. It helps validate the original endpoint routing and code baseline; it does **not** reveal private ANLGarden server PHP/session processing or remove its server-side VIP enforcement. Changing an endpoint alone does not implement an email-only pre-submit CGM gate.

## Screenshot context

The shared PlayPark CM screenshot says: **“hi! it's not official but you may use it at your own discretion.”** This supports tolerance of ordinary unofficial APK use at the time, but is not a technical safety certification or permission to forge sessions or bypass access controls. A referenced July 2020 beta announcement is not proof this specific uploaded binary was built in 2020.

## Practical decision

Continue the pre-submit CGM email-only membership integration for the standard inner CONFIRM path while preserving native `Login()`/`SendLogin()` behavior and all legitimate upstream decisions. The legacy file adds no independently runnable, authenticated Garden session backend. Do not repeat client mapping or try to repurpose a past captured session as proof of authorization.

An offline reproducible comparison script and JSON evidence were packaged as `Legacy_BASE_vs_CGM_Offline_Audit.zip` for the user.