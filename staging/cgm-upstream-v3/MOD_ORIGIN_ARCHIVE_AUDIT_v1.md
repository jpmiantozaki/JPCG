# MOD-origin session evidence — finite archive audit

2026-10-23. Scope: read-only search of existing user-owned Windows diagnostic log files, no Android/Unity/Ghidra reruns, no live network capture or deployment.

The earlier October 8 success in retained archives originated from BASE (anlgarden.com) and showed stage0 original packet ~58 bytes, success response data packet ~63 bytes, relay session ~12 bytes, later stage1. The MOD-account failure also used **BASE origin**, where it returned a VIP rejection. The current project evidence does not contain a confirmed successful stage0+stage1 exchange **originating from MOD** (m.cabahug.xyz). Original BASE/MOD ARM64 client code and classes.dex were previously verified identical, but remote server implementations were not.

To resolve this specific evidence gap without asking for real packets, passwords or sessions, an offline local package `CGM_MOD_Origin_Evidence_Audit.zip` was created in conversation (SHA256 `34a14c84b59d94090c45da0a752caff74cbe31f2a78b71b7a937b4e62061f2f3`). It contains `lib/Audit.php`, `bin/run_audit.php`, `tests/run.php`, README and technical decision. PHP syntax checks and 18/18 synthetic safety/classification tests PASS; ZIP CRC PASS. Its actual archive scan has **not yet run on the user's PC**.

Operation: user extracts to `D:\CGMTEST\CGM_MOD_Origin_Evidence_Audit\` and runs
`& 'D:\CGMTEST\PHP\php.exe' 'D:\CGMTEST\CGM_MOD_Origin_Evidence_Audit\bin\run_audit.php' 'D:\CGMTEST'`.

Only structured `[CGM_SESSION_RESPONSE]` JSON markers are parsed. A MOD positive record requires `upstream_host=m.cabahug.xyz`, HTTP200, logical error=false, stage0 or stage1, nonempty packet/session metadata, valid hex indicator. Includes source path fingerprint, not path or source lines, and never exports packet bodies, passwords, identifiers, cookies or session values. Excludes the audit package itself. Aggregates over all records even if detailed report is capped. Does not access other people's servers or APKs. Matching records are **candidates**, not proof of legitimate provider acceptance or same session. Missing records reflect missing evidence in scanned files, not evidence that the MOD backend fails.

Decisional next step: if an actual MOD-origin candidate positive shape exists, compare its response/relay session lengths and stage semantics to earlier BASE shapes. Otherwise stop speculative endpoint equivalence claims until there is credible MOD-origin success evidence or suitable documented session processor. Do not manufacture a successful game session.


## User Windows run — 2026-10-23

User executed the archive audit on `D:\CGMTEST` and shared only the terminal result:

```text
Files considered: 3231
Structured session records: 4
Scan complete: YES
BASE: stage0-positive=0 stage1-positive=0 rejected=0
MOD: stage0-positive=0 stage1-positive=0 rejected=0
RESULT: NO_VERIFIED_MOD_SUCCESS_METADATA_IN_SCANNED_FILES
Sanitized report: D:\CGMTEST\CGM_MOD_Origin_Evidence_Audit\results\mod_evidence_20261023_123335_f739b7.json
```

**Interpretation:** The user Windows scan found only four parseable structured markers, zero qualifying BASE and MOD response outcomes. Do **not** imply this means either service is broken: prior archived BASE success was already evidenced in separately retained narrative/sanitized results. Current tool only parses explicit `[CGM_SESSION_RESPONSE] {...}` lines; it ignores ZIP/7z and raw/older format logs, logs with >8192-character lines, and individual files >8MB. `scan_complete=YES` indicates the traversal stayed below the traversal cap without a propagated iterator error, not that all possible archived formats were searched.

**Next step is targeted classification, not repeat entire archive scan:** inspect the already-created sanitized JSON for `counts.UNKNOWN.response_records`, `files_skipped`, and the four allowed `records[]` fields: `origin,stage,http_status,error,classification,data_present,relay_session_present`. No raw log/ciphertext requested. If four entries are diagnostic stubs or missing `upstream_host`, no MOD-positive evidence remains. The missing MOD-origin successful session response should remain an explicit data gap; do not fabricate upstream packet adaptation.


## Four sanitized records — direct user-provided table (2026-10-23)

The user inspected the sanitized audit JSON and provided these exact classifications (all `origin=UNKNOWN`, no `http_status` value):

| stage | error | classification | data_present | relay_session_present |
|---|---|---|---|---|
| 0 | False | INCOMPLETE_SUCCESS_METADATA | True | True |
| 1 | False | INCOMPLETE_SUCCESS_METADATA | True | True |
| 1 | False | INCOMPLETE_SUCCESS_METADATA | True | True |
| 1 | False | INCOMPLETE_SUCCESS_METADATA | True | True |

These are four **success-shaped** responses (one stage0 and three stage1), not authenticated provider/Garden success verification. The missing origin and HTTP status explain why the strict archive audit classified neither BASE nor MOD positive. They might be pre-existing historical BASE diagnostics, but that attribution is **not established**. The four records cannot be assumed to belong to a single session without preserved correlation metadata. Do not repeat the global archive scan. The next finite evidence action is **provenance-only**: inspect safe record source fingerprints/labels and adjacent structured `[CGM_SESSION_BRIDGE]` upstream_host markers, avoiding raw log lines or query strings, then classify origin if and only if timestamp/source correlation supports it. If no such metadata exists, record the origin as unresolvable instead of inventing MOD success.


## One-source provenance final outcome — 2026-10-23

The user executed the source-specific provenance script against `source-91fcda21919f` and provided:

```text
source_matched: YES
response_records: 4
bridge_origin_markers: BASE=0 MOD=0 OTHER=0
#1 stage=0 bytes=67 relay_session_bytes=12 origin=UNKNOWN http=MISSING hex_flag=MISSING servertype_field=MISSING status_field=MISSING upstream_host_field=MISSING
#2 stage=1 bytes=22 relay_session_bytes=12 origin=UNKNOWN http=MISSING hex_flag=MISSING servertype_field=MISSING status_field=MISSING upstream_host_field=MISSING
#3 stage=1 bytes=25 relay_session_bytes=12 origin=UNKNOWN http=MISSING hex_flag=MISSING servertype_field=MISSING status_field=MISSING upstream_host_field=MISSING
#4 stage=1 bytes=17 relay_session_bytes=12 origin=UNKNOWN http=MISSING hex_flag=MISSING servertype_field=MISSING status_field=MISSING upstream_host_field=MISSING
```

**Final classification:** `UNATTRIBUTABLE_SUCCESS_SHAPED_RECORDS`, not verified MOD or BASE success. All four are from one source file, not necessarily one login session. `data_hex_valid=false` in the previous audit was a default substituted for missing fields, **not** a proven invalid hex response. The stage-0 67-byte response and 12-byte session cannot be attributed to MOD or compared as paired transformation with the archived 58-byte BASE request / ~63-byte BASE response. No source-local origin markers exist.

**Stop condition reached:** do not keep scanning this source or repeating membership/continuity tests; it cannot supply upstream provenance that was not logged. The next engineering input must be a genuine source-attributed successful MOD-origin exchange obtained via permitted/legitimate access, documented upstream processor behavior, or directly usable owned server implementation. Do not route credential-bearing packets to unknown servers, forge authentication success, or interpret synthetic v3 tests as independent authenticated Garden access.
