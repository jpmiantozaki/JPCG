# Phase 40AF — Real CGM CONFIRM button, reversible offline binding test

**Date:** 2026-10-21. **Status:** One-byte reversible change in an *extracted Unity scene copy* validated. **Not** an integrated Auth-First APK, and **not** installed/tested against game servers. **No** original APK, Nox, production, PlayPark or ANLGarden configuration changed.

## Evidence

- Exact **archived CGM input APK** SHA-256: `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`. This differs from the current Nox62032 installed app archive; do **not** extrapolate the byte offset across different archives.
- Original UnityFS `assets/bin/Data/data.unity3d` contains `level1` (813,428 bytes), Unity serialized version 22, Unity 2021.3.45f1. Parsed **5,489** objects from its serialized object metadata table.
- Real **inner CONFIRM** Button, Path ID `4300`, is a 412-byte MonoBehaviour whose serialized persistent listener targets `LoginServerManager` Path ID `4610`, method `Login()`; original `m_CallState` is `2` (Runtime Only).
- The `m_CallState` integer is at **scene-relative absolute decimal offset 606600**, within the PathID4300 serialized object. On an **isolated extracted scene copy only**, changed `2 -> 0` (Off). Byte diff count: **exactly 1**.
- Separate **outer yellow LOGIN** Button PathID `4164` still calls `ConnectToServer` and was byte-for-byte unchanged.
- Original scene SHA-256 `aba884bc473798fdd844cafe66c2e6ba8894f69dbc07a9f241216a9f7031c353`; changed test scene SHA-256 `d646a0ba37ecc987de8ae5a0f81989c4741c77e13b3f8dd95f287e5635194e40`.
- Reverting the same byte restored the **exact original scene**. No native `Login()` or `SendLogin()` instructions were patched; no APK repacking/signing, installation or user credentials.

The reproducible Python3 source proof, SHA-constrained parser and report are packaged in the conversation artifact `CGM_Phase40AF_Real_Confirm_Binding_Proof.zip`. The proof checks the real archived APK's SHA-256 and exact object target+method before creating any scene copy. The package does **not** include a deployable modified game APK.

## What the result means

**Proved:** the inherited original persistent UI callback can be precisely identified and switched off without changing unrelated serialized scene bytes. This is a potential prerequisite to an in-place **source-based** integration, not a working membership gate.

**Not proved:** the recovered Phase40AE Java/C# gate can be installed and executed inside existing IL2CPP; there is no compiled managed `CgmEmailLoginGate` in the source APK, and the exported C# methods from AssetRipper are stubs. Switching the listener off alone **makes login unusable** and must not be distributed as an Auth-First build. A safe executable original-login continuation still needs an independently verified Unity/IL2CPP bridge. A valid CGM ACTIVE decision cannot replace ANLGarden relay VIP or genuine PlayPark/Game/Garden authorization.

**Next action:** establish a real executable pre-submit adapter for standard inner CONFIRM, preserving the entire original native `Login()`/ `SendLogin()` path. Only then consider a reversible, separately signed staging APK for the disposable Nox62032; leave Nox62030/62031 untouched.

## User-provided community post

Audition Mobile Garden's page announcement dated September 17, 2025 describes its unofficial app as safe and reports no bans for Mobile Garden alone. This is a statement by the **unofficial tool's page**, not independently verified PlayPark authorization to bypass separate upstream entitlement or authentication. Ordinary unofficial client usage and third-party VIP/session access are different permissions.
