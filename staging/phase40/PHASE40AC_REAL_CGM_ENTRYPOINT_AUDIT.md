# Phase 40AC - Actual CGM native credential-submission entrypoints
**Status:** Read-only offline audit of real, byte-identical CGM ARM64 `libil2cpp.so`, matching SHA-256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`. No APK editing, Nox, production/backend access, passwords, or real login.

## New verified finding: two direct callers of SendLogin
`LoginServerManager.SendLogin(string username, string password, string clientVer, bool fbLogin=false)` is at RVA `0x6A80C0`.

| Calling path | Direct BL instruction | Fourth argument | Relationship |
|---|---|---|---|
| Normal second CONFIRM: `LoginServerManager.Login()` RVA `0x6A86E8` | `0x6A8868` | `w4=0` at `0x6A8864` | ordinary email/password login |
| Legacy WebView: `LoginServerManager.<WebViewOnPageFinished>b__30_0(string content)` RVA `0x6A8890` | `0x6A8A20` | `w4=1` at `0x6A8A10` | alternate/Facebook-style login, runtime reachability not proven |

These were confirmed using executable ELF-section AArch64 `BL` scanning **and** disassembly of the original code. The dump.cs method mapping was cross-checked. Exactly two **direct** BL callsites appear; indirect calls via BLR, delegates, reflection, UnityEvents, 32-bit library or another installed APK may exist.

## Standard Login() details
- Checks `isLoginProcess` (`this+0x48`) and `isLoginSuccess` (`+0x4A`); checks empty `txtUsername` (`+0x18`) and `txtPassword` (`+0x20`) fields before submission.
- If `session` (`+0x50`) is absent, invokes `ConnectToLoginServer()` (RVA `0x6A665C`) before proceeding.
- Reads `InputField.text`, and invokes `System.String.Replace` RVA `0xCB6278` on username before it is passed to `SendLogin`. Replacement literal values have not been resolved; do not invent case/trim behavior.
- Immediately **after** `SendLogin` returns, `0x6A8870` writes `isLoginProcess=true`. A naive async hook that returns before original packet transmission leaves the state flag set and may block retry. `SendLogin` arguments include the password: a common hook must not expose/forward/store it in the CGM membership service.

## Other verified client callsites
- `GameServerManager.SendVerifyCCU` => `RelayWebManager.SendRelayInfo` at `0x69A6B8`.
- `GameServerManager.SendLogon` => `SendRelayInfo` at `0x69BB8C`.
- `SendRelayInfo` => `RelayServerData` at `0x6A8448`.
- `GardenManager.LoadGarden` => `RelayVerifyVIP` at `0x640FB8`.
- `LoginServerManager.OnPacketReceived` => `GameServerManager.ConnectToGameServer` at `0x6A7E8C` on original login-success handling.

## Decision
A standard-CONFIRM-only pre-submit gate must explicitly exclude or handle the legacy web path; a common `SendLogin` boundary covers two direct calls but introduces sensitive credential/IL2CPP GC lifetime, Unity thread and state restoration risks. Existing source workspace is **not buildable Unity source**. No proven safe native hook/adapter exists in current Nox x86_64/Houdini. The next implementation step needs a demonstrable owner-thread, GC-safe ARM64 test environment to defer/resume original submission exactly once using an **email-only** `authfirst.php` preflight. CGM ACTIVE does not replace ANLGarden VIP, PlayPark authentication or Garden session grants; do not alter those server responses.

Offline reproducible source and test: `CGM_Phase40AC_Real_Entrypoint_Audit.zip`, with Python `scan_callsites.py`, `validate_real_login.py`, their JSON output and full report. Validation passed on the exact library and dump.cs with all three assertions: two direct callers, false/true mode flags and original login-in-progress flag write. No real credentials or provider traffic.

## Scope correction — FB login explicitly excluded (user confirmation)

The user reconfirmed that `LoginServerManager.<WebViewOnPageFinished>b__30_0` direct `SendLogin()` call at `0x6A8A20` **is the legacy Facebook login path**, which is obsolete and **out of scope** for CGM Auth-First. Do **not** present it as an outstanding coverage blocker or require hooking it. It remains helpful as a static fact only. The targeted supported user interaction is **yellow LOGIN -> credential fields -> inner CONFIRM -> `Login()` (RVA `0x6A86E8`) -> `SendLogin()` (`0x6A80C0`) through direct call `0x6A8868` with `fbLogin=false`**. Gate only the ordinary email/password login attempt, preserve its native state handling and genuine upstream decisions, and do not make assumptions about other standard non-FB entry paths without evidence.
