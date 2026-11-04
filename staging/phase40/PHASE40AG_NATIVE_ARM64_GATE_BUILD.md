# Phase 40AG — Native ARM64 CGM membership gate library

**Status:** Actual AArch64 ELF shared library compiled and host-tested. **Not installed, hooked or loaded in the CGM APK; not a functioning Garden login.** No Nox device, PlayPark, ANLGarden service, production CGM/Render, or GitHub main branch modified.

## Scope correction

The user's purported legacy download was in fact another updated **BASE** APK. Stop treating it as a distinct historical build. Use the archived CGM compiled APK as the authoritative native testing baseline, and only reuse the BASE comparison to establish shared compiled code.

## Artifact and checks

Conversation download package: `CGM_Phase40AG_Android_ARM64_Native_Gate.zip`, containing source C ABI code, header, tests, README, cross-built ARM64 `libcgm_authfirst_gate.so`, and test logs.

- SHA256 ARM64 shared library: `18e449bcfb7bff8ef4e4069db1fbbc28e7d657c20cc1cfc5f552630ac8a690d7`.
- Compiled with Clang `-target aarch64-linux-android26 -fPIC -ffreestanding -nostdlib -shared`.
- Binary inspection: ELF64 AArch64 `ET_DYN`, shared-library SONAME `libcgm_authfirst_gate.so`, and dynamic exported symbols `JNI_OnLoad`, `cgm_gate_abi`, `cgm_gate_state`, `cgm_gate_begin`, `cgm_gate_reply`, `cgm_gate_take`, `cgm_gate_cancel`. No external shared-library NEEDED dependencies.
- **34/34 native C host fixture assertions passed**, including ACTIVE, inactive/error, timeouts, expiry boundary, identity case change, duplicate callbacks, account switching, cancellation and once-only continuation.
- Host compiler with ASan + UBSan also passed same 34 fixtures. ZIP archive CRC integrity passed.
- No passwords, packet material, session tokens or live usernames passed into or stored by module.

## Pre-submit API contract

`cgm_gate_begin(current_ui_email, monotonic_ms)` starts a generation-bound attempt on the **Unity owner thread**, or denies malformed identifiers. It contains no network transport. A separate HTTPS email-only client must validate TLS, response schema/request-id, server time, expiry and TTL from existing JPCG `authfirst.php`, then dispatch to the Unity owner thread. `cgm_gate_reply(generation, unchanged_ui_email, ACTIVE|INACTIVE|ERROR, monotonic_ms, expiry_epoch_ms, server_epoch_ms, ttl_ms)` records a fresh decision. `cgm_gate_take(generation,current_ui_email,monotonic_ms)` grants **one** continuation for a still-identical, unexpired ACTIVE membership. `cgm_gate_cancel` invalidates pending state and wipes the held email. The module does not accept a password or generate a game/session result.

The API is intentionally **single-owner-thread** and test-fixture ASCII-email-only. It is not general Unicode email support or trusted account-ownership evidence.

## Unresolved practical integration

- The original inner CONFIRM Unity Button `4300` still invokes `LoginServerManager.Login()` directly. Phase40AF proved its existing `m_CallState` can be switched Off in an extracted serialized scene copy, but **no new executable Unity callback** was installed. Merely switching the listener Off would break login.
- This compiled ARM64 library is **not automatically loaded** by placing it inside an APK. The existing Java `classes.dex` has no CGM loader, and `JNI_OnLoad` is an exported ABI signature only. A tested Android/Unity load point and actual IL2CPP continuation that preserves `Login()` and `SendLogin()` state still need to be built and verified.
- Previous Nox x86_64/Houdini testing has not proven a safe ARM64 guest hook. No new Nox attempts conducted.
- CGM ACTIVE does **not** substitute for ANLGarden relay VIP or official PlayPark/game/Garden authorization, and no upstream rejection will be overwritten with fake `error=false`.

**Next GO/NO-GO checkpoint:** demonstrate safe loading and Unity-side binding replacement on a separate staging test build; test email-only preflight end-to-end using synthetic accounts **before** attempting permitted real-game authentication. Do not distribute a nonfunctional listener-off APK as a purported fixed release.
