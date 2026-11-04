# CGM Phase 40CD — Trusted provider handoff and Unity 2021 response-type probe

**Date:** 2026-10-26  
**Status:** PHP handoff source and synthetic Editor probe staged. **No PlayPark/ANLGarden session issuance, deployment, APK changes, or original-game test.** Phase40BP remains restored on Nox62033; rollback Nox62030 untouched.

## Source now present on this staging branch

- [`src/ProviderHandoffGate.php`](src/ProviderHandoffGate.php) — Four independently trusted, server-side dependencies. An authorized provider must verify an existing session and attest stable issuer/subject, verified account email, audience, fresh attestation, matching request-attempt ID, expiry, **official game acceptance, official Garden authorization and any intermediary entitlement**. A server-owned verified link binds issuer+subject to **one exact** CGM membership key, and a server-owned membership lookup must return ACTIVE/unexpired. Finally an external **durable atomic** one-use attempt store must consume the bound request once. No client-supplied ACTIVE flag, alias guessing, password, CCU/userSN, session creation, packet generation or VIP rejection rewriting.
- **Default wiring is DENY:** unconfigured provider, account-link, membership and nonce interfaces cannot approve anything. A positive **synthetic-only** policy result is `PRECONDITIONS_ATTESTED_ONLY` with `session_issued=false`, `game_packet_generated=false`, `provider_session_created=false`. No production endpoint calls this class.
- [`unity/CgmPhase40CDJsonProbe.cs`](unity/CgmPhase40CDJsonProbe.cs) — Editor-only Unity 2021.3 probe using synthetic JSON with matching `MGRelayResponse` field names/types. **22 labeled cases**, both generic and non-generic `JsonUtility.FromJson`. Measures missing `servertype` (C# default) versus JSON integer 0/1, quoted numerics, floating numbers, explicit null, booleans, duplicate keys, and `uint vip_expiry` variants. Logs only labels, observed integer values, exception class names and Unity version. **Unity runtime not executed here.**

Both GitHub blobs were fetched back and matched the **exact** source blob SHA-1 values of the locally tested/package files:

| Staged file | Git blob SHA-1 |
|---|---|
| `src/ProviderHandoffGate.php` | `6d93dca68e398952e779987462eb30b92182e56e` |
| `unity/CgmPhase40CDJsonProbe.cs` | `e2d7fd9f0c823c2cce09139c57ef8edcde10f4e0` |

## Offline package and honest test boundary

**Package:** `CGM_Phase40CD_Provider_Handoff_Unity_Probe_OFFLINE.zip` (24,571 bytes).  
**SHA-256:** `a89f521fb705523fbff5c394ae878a2ebb6234f5ff23506b88012cc03da143f4`.  
Contains the new PHP gate, its full standalone synthetic test suite, an integration test, copied original Phase40CC source/stubs, 22-case Unity Editor-only project, one optional Windows runner, validation notes, per-file SHA manifest. CRC/SHA integrity validated after clean extraction.

**Actually run in isolated authoring environment:**
```text
PHASE40CD_PROVIDER_HANDOFF_OFFLINE_PASS assertions=632
PHASE40CD_INTEGRATION_BOUNDARIES_OFFLINE_PASS assertions=14
PHASE40CC_UPSTREAM_FOUNDATION_OFFLINE_PASS assertions=163
PHASE40CD_PHP_LINT_PASS
PHASE40CD_PACKAGE_CLEAN_EXTRACTION_SHA256_PASS
```
The provider-positive case uses a synthetic fake provider; no real game authorization occurred. Most of the 632 assertions are repeated fail-closed/redaction invariants over many denial scenarios. The test-only in-memory consume-once store is not transactional production code. The authored Unity Editor C# code passed only static source checks: **NOT compiled or run** against Unity here.

## Optional user-owned Windows Unity test (NO Nox, NO PHP, NO APK)

Extract the package into `D:\CGMTEST` and run its single `scripts\run_unity_probe.ps1`. The script discovers Unity Editor **2021.3.45f1** in standard Windows locations, opens the separate minimal synthetic-only Unity project, and reports only `CGM40CD_` log markers. No genuine packet, credential, session, server or GameObject is required; if Unity is absent, it simply reports no test. Alternatively put the C# file in an existing local Unity 2021.3 project's `Assets/Editor` and choose **Tools > CGM > Phase 40CD > Run Offline JSON Probe**. Results are *Editor evidence only*, not direct Nox/IL2CPP player evidence.

Unity 2021.3 states missing `FromJson` fields default to C# defaults ([version-matched manual](https://docs.unity3d.com/2021.3/Documentation/ScriptReference/JsonUtility.FromJson.html)). String/float/null/duplicate-key handling for the original player is not demonstrated by the docs or PHP tests and remains open until observed. Historical BASE logs' `servertype=null` means the old PHP logger did **not** see an in-range integer; the real JSON type was discarded. Phase40CB corrected the similarly misleading `vip_expiry_present` name: it tests `is_int()`, not actual key presence.

## Real integration stop conditions

No usable licensed/authorized compatible intermediary or official session verifier was established. This prototype **cannot** create valid GameServer/Garden packets, bypass another service's VIP controls, issue a PlayPark login, or prove a compatible transformed packet algorithm. Do not wire it to `public/session.php`, change deployed Render/Cloudflare, route user passwords to unknown services, or change the original APK. A trusted server-side verifier, true official account linkage, independent intermediary entitlement, real session acceptance, privacy-audited transport and durable atomic storage must exist before a valid handoff can be claimed.

**Source history:** [Phase40CC](README.md), [Phase40CB](../../docs/phase40cb-original-upstream-response-type-contract-2026-10-26.md), [Phase40BZ](../../docs/phase40bz-identity-link-and-relay-privacy-2026-10-26.md).
