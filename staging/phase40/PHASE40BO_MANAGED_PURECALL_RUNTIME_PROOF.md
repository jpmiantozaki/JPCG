# Phase 40BO runtime checkpoint — benign managed method verified

Date: 2026-10-23. Source: user-provided actual disposable Nox62033 PowerShell and Android logging output.

The Phase 40BO test returned UNITY_LOOP_JNI_MANAGED_PURECALL_PASS.

Timestamped markers:
- 12:54:41.777 native library loaded
- 12:54:56.781 queued harmless Java callback, attempt 1
- 12:55:02.229 Java callback ran on the original UnityPlayer loop thread
- 12:55:02.229 managed pure-call status 220
- 12:55:02.229 System.String.IsNullOrEmpty(null) returned true

The run's report and filtered log remain on the user's Windows computer at D:\CGMTEST\CGM_Phase40BO_Managed_PureCall_UnityLoop\runtime-results\Phase40BO_20261023_125148_bd0bc6fc.

Confirmed: a benign Java-to-JNI-to-IL2CPP call returned its expected value from the Unity loop context. No game Login method was invoked and no private credentials or server authentication were tested.

Limitations: Does not establish managed object lifetime and thread safety in general, original LoginServerManager identity, button interception, CGM membership gate, official login, or upstream Garden authorization.

Suggested next work: read-only instance identity/lifetime validation, with explicit object validity checks from the known Unity loop and no game method calls. Avoid repeating earlier pure-call and network-routing milestones. Previous signed build should remain on disposable Nox62033, and any future install must require an exact predecessor hash and include a reversible rollback.
