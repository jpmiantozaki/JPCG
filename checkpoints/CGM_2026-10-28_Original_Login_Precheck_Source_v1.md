# CGM Auth-First - source-only original login continuation checkpoint

Date: 2026-10-28. Status: STATIC TESTS ONLY; NO Unity, APK, official login, GameServer or Garden acceptance claimed.

## Owned upstream

JPCG `public/authfirst.php` already provides email-only membership status over HTTPS. A new public port or server login handler is **not** needed for this precheck. Original `public/session.php` remains a forwarding bridge to an external mobile intermediary, so endpoint discovery and known 18805/18806/20840 ports do not complete the missing authorized game session processor.

## Staged source integration (not applied to main or deployed)

A C# client-source patch package was prepared in this conversation: `CGM_AuthFirst_Original_Login_Precheck_Source_v1_2026-10-28.zip`. The build: 
- POSTs only `schema`, `request_id`, `email` to `https://jpcg.onrender.com/authfirst.php`
- Accepts only matching fresh LIVE `ACTIVE`; blocks all other outcomes
- Correlates request generation and original username; cancels after edits/panel hide/timeout
- Rewires only the inner original CONFIRM binding; retains the same unchanged `LoginServerManager.Login()` target as a separate callback
- Leaves original login continuation disabled by default in offline AssetRipper export
- Never reads/sends PlayPark passwords to JPCG or creates fake official sessions

The source package was static-audited 19/19 and ZIP verified. Editor selftests contain 18 synthetic cases but have not been executed in Unity. The original AssetRipper `Login()`/`SendLogin()` functions are offline stubs: compiling source into the export does not make the original APK authenticate. Real APK integration requires an independently verified, authorized extension/patching mechanism; no Frida attach or native hook success was established on experimental Nox 62033. Rollback Nox 62030 is out of scope.

## Stop criteria

Do not deploy or enable the original callback while source is stubbed, the direct CONFIRM event still bypasses the gate, source tests fail, or account identity/session binding is not validated. Never reroute game passwords to the CGM membership gateway.