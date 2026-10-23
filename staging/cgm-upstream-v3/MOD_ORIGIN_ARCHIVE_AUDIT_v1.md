# MOD-origin session evidence — finite archive audit

2026-10-23. Scope: read-only search of existing user-owned Windows diagnostic log files, no Android/Unity/Ghidra reruns, no live network capture or deployment.

The earlier October 8 success in retained archives originated from BASE (anlgarden.com) and showed stage0 original packet ~58 bytes, success response data packet ~63 bytes, relay session ~12 bytes, later stage1. The MOD-account failure also used **BASE origin**, where it returned a VIP rejection. The current project evidence does not contain a confirmed successful stage0+stage1 exchange **originating from MOD** (m.cabahug.xyz). Original BASE/MOD ARM64 client code and classes.dex were previously verified identical, but remote server implementations were not.

To resolve this specific evidence gap without asking for real packets, passwords or sessions, an offline local package `CGM_MOD_Origin_Evidence_Audit.zip` was created in conversation (SHA256 `34a14c84b59d94090c45da0a752caff74cbe31f2a78b71b7a937b4e62061f2f3`). It contains `lib/Audit.php`, `bin/run_audit.php`, `tests/run.php`, README and technical decision. PHP syntax checks and 18/18 synthetic safety/classification tests PASS; ZIP CRC PASS. Its actual archive scan has **not yet run on the user's PC**.

Operation: user extracts to `D:\CGMTEST\CGM_MOD_Origin_Evidence_Audit\` and runs
`& 'D:\CGMTEST\PHP\php.exe' 'D:\CGMTEST\CGM_MOD_Origin_Evidence_Audit\bin\run_audit.php' 'D:\CGMTEST'`.

Only structured `[CGM_SESSION_RESPONSE]` JSON markers are parsed. A MOD positive record requires `upstream_host=m.cabahug.xyz`, HTTP200, logical error=false, stage0 or stage1, nonempty packet/session metadata, valid hex indicator. Includes source path fingerprint, not path or source lines, and never exports packet bodies, passwords, identifiers, cookies or session values. Excludes the audit package itself. Aggregates over all records even if detailed report is capped. Does not access other people's servers or APKs. Matching records are **candidates**, not proof of legitimate provider acceptance or same session. Missing records reflect missing evidence in scanned files, not evidence that the MOD backend fails.

Decisional next step: if an actual MOD-origin candidate positive shape exists, compare its response/relay session lengths and stage semantics to earlier BASE shapes. Otherwise stop speculative endpoint equivalence claims until there is credible MOD-origin success evidence or suitable documented session processor. Do not manufacture a successful game session.
