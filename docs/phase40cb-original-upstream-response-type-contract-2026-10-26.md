# Phase 40CB — Original upstream response-type contract, historical diagnostic correction

**Date:** 2026-10-26 (Asia/Manila)  
**Status:** Read-only source/native/archive audit with synthetic offline classifier. **No production route, original APK, emulator, upstream or deployment changed.**

## 1. Original Unity serializer: NEW source-backed finding

Original `CGM-62031-Local-SIGNED.apk` ARM64 `libil2cpp.so` SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8` was re-extracted and hash verified. The same pinned original `dump.cs` declares:

- `[Serializable] MGRelayResponse` fields: `bool error` (+0x10), `string message` (+0x18), `string data` (+0x20), **`int servertype`** (+0x28), `string session` (+0x30), **`uint vip_expiry`** (+0x38).
- `RelayWebManager.ServerType.LoginServer = 0`, `GameServer = 1`.
- `MGRelayResponse.CreateFromJSON(string)` ELF RVA `0x6B0084` loads its generic deserializer metadata and tail-branches at `0x6B00C8` to `0x81E0D0`.
- The original `dump.cs` maps `0x81E0D0` to the generic `UnityEngine.JsonUtility.FromJson<object>` implementation. Independent ARM64 disassembly verified the target; it invokes the non-generic `JsonUtility.FromJson(string, Type)` at `0x10CE2C8`.
- The normal mobile response callback `0x6B088C` uses the resulting numeric `servertype` to select the LoginServer (0) or GameServer (1) connection for packet dispatch. This does **not** itself prove official acceptance.

Version-matched **Unity 2021.3** official documentation says fields absent from JSON receive C# default values: an absent `int servertype` would ordinarily deserialize as `0` (LoginServer), not `1`. This is **a conditional rule for absent fields**, **not** proof that the upstream actually omitted the field, nor proof about numeric-string, floating-number, explicit-null or duplicate-key coercion in this original player.

Reference: https://docs.unity3d.com/2021.3/Documentation/ScriptReference/JsonUtility.FromJson.html

## 2. Exact v3 logger semantics — what old data CAN and CANNOT establish

Current JPCG staging `public/_cgm_session_diagnostic.php` contains:

```php
$serverType = $payload->servertype ?? null;
$record['servertype'] = is_int($serverType) && $serverType >= 0 && $serverType <= 255 ? $serverType : null;
$expiry = $payload->vip_expiry ?? null;
$record['vip_expiry_present'] = is_int($expiry);
$record['vip_expiry_positive'] = is_int($expiry) && $expiry > 0;
```

- **`servertype=null`** in a *v3 diagnostic* means that PHP did **not** decode an integer in [0,255]. Therefore conventional JSON integer `0` or `1` is **inconsistent** with that diagnostic; those would have been logged as the corresponding integer. The actual JSON remains unknown: key absent, explicit null, string (including quoted digits), floating point, boolean, array, object or out-of-range integer all collapse to null.
- **CORRECTION to Phase 40CA:** v3 `vip_expiry_present` is **not a key-presence test**! It is `is_int()`. The previous phrase that Stage0 *lacked* `vip_expiry` was too strong. `false` means value was not a PHP integer, **not** necessarily missing. `true` means PHP integer; paired with `vip_expiry_positive=false` it means **nonpositive integer**, not necessarily exactly zero.
- `data_present` / `session_present` in v3 are similarly shape/value tests, not an account/session issuer.

Read only sanitized `[CGM_SESSION_RESPONSE]` JSON from historical `cgm_trace_research/Pasted text(3).txt` (**13**) and `Pasted text(4).txt` (**7**) for a total of **20** records from three BASE-origin clusters. No protected credential URLs or token values were extracted or published.

| v3 observation | Counts | Supported claim |
|---|---:|---|
| HTTP 200 and `error=false` | 20/20 | Application-positive envelopes, not official account authorization |
| `servertype=null` | 20/20 | No PHP-decoded in-range JSON integer; original type/key unknown |
| Stage0 + `vip_expiry_present=false` | 3/3 | PHP decoded a noninteger-or-missing `vip_expiry` |
| Stage1 + `vip_expiry_present=true` and `vip_expiry_positive=false` | 17/17 | PHP decoded a nonpositive integer `vip_expiry` |
| Hex-shaped `data`, `session` string length 12 | 20/20 | Shape/length only; does not prove GameServer/Garden acceptance |

The historical evidence does **not** reveal whether the upstream emits quoted numeric `servertype`, floating-point `servertype`, missing type, or another representation. Combining a missing integer default of 0 with apparent Stage1 progression raises a question, **not** a demonstrated explanation.

## 3. New staging-only source package, validation

Artifact created **locally, source only**: `CGM_Phase40CB_Response_Type_Contract_Source_OFFLINE.zip`; SHA256 `556e9848e377e116dfa00f89b519dcbc2063c3102f92add2dfdb1915f8e290ca`; **13,461 bytes**. Includes `src/CgmResponseTypeAudit.php`, `tests/offline_test.php`, README, full audit, validation and a SHA manifest. This ZIP is provided separately to the user; it has **not been integrated into JPCG `public/`**.

`CgmResponseTypeAudit` distinguishes JSON key **missing** versus explicit `null`, `integer`, `float`, `string`, `boolean`, `array`, `object`. For integers only, it emits safe `integer_0`, `integer_1`, `integer_other_byte`, `integer_outside_byte` categories; other response fields expose type/boolean shape only. No raw packet, session, login, message, or credential string is returned. It can receive an **injected pre-existing decryptor** but does not perform HTTP, logging or authentication. It also interprets legacy-v3 summaries without pretending to recover lost values.

Offline PHP lint PASS; **296/296 deterministic synthetic assertions PASS**, including exception and secret-redaction cases. The archive passed ZIP CRC, SHA manifest verification and a clean extraction rerun:

```text
PHASE40CB_RESPONSE_TYPE_CONTRACT_OFFLINE_PASS assertions=296
ZIP_CRC_AND_SHA256_PASS
```

These tests use PHP's JSON decoder, **not Unity's**. They do **not** prove how Unity coerces types, what real upstream emitted, or that any packet/game session was accepted.

## 4. Finite next evidence and stop conditions

A meaningful next measurement would be **type/presence-only** inspection of a response obtained through a legitimate, authorized origin or pre-existing non-secret fixture, **without** storing original JSON, protected bodies, credentials or relay tokens. Alternately use a standalone Unity 2021.3 synthetic JSON fixture to resolve Unity's coercion behavior, with no network. Do not rerun Nox login tests or create synthetic GameServer successes merely to increase PASS counts.

**Not proven:** a valid independent session processor, authenticated provider account linkage, original-server CCU/Garden acceptance, replacement of upstream VIP decisions or Unity treatment of string/float `servertype`.

**No changes:** repository default branch, JPCG `public/`, Render/Cloudflare, original signed APK, experimental Nox62033 or rollback Nox62030. Phase 40BP remains restored/verified.
