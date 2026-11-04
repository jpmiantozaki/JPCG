# Phase 40CD — Unity Editor 2021.3.45f1 runtime probe results

**Date:** 2026-10-26 (Asia/Manila)  
**Evidence source:** user-pasted filtered Unity Editor log, obtained with the locally extracted **Phase 40CD** Editor-only synthetic project.  
**Scope:** 22 fabricated JSON cases, C# `RelayResponseFixture` fields `bool error`, `int servertype`, `uint vip_expiry` (plus strings), both `JsonUtility.FromJson<T>` and `JsonUtility.FromJson(string, Type)`. No live upstream, packet, credential, APK, GameServer, Garden, Nox, or provider session in this experiment.

## Observed result: probe executed despite PowerShell runner failure

```text
CGM40CD_PROBE_BEGIN unity=2021.3.45f1 count=22 network=none original_apk=untouched
CGM40CD_PROBE_END unity=2021.3.45f1 cases=22 generic_exceptions=0 nongeneric_exceptions=0 official_auth=NOT_TESTED garden=NOT_TESTED
```

The original PowerShell runner reported `Unity Editor probe failed` on a nonzero **Unity process exit code**. This is **not evidence that the probe itself failed**: all 22 `CGM40CD_CASE` rows and the terminal marker were present. The reason for Unity Editor's nonzero exit was not provided in the filtered log and is **not diagnosed here**. Do not rerun the probe to re-prove completed measurements unless further information is specifically needed.

## Exact observed scalar results

Every row yielded identical `generic` and `nongeneric` results, **zero deserialization exceptions**.

| Test case | `servertype` | `vip_expiry` | `error` |
|---|---:|---:|---|
| missing_type | 0 | 0 | false |
| integer_zero | 0 | 0 | false |
| integer_one | 1 | 0 | false |
| string_zero | 0 | 0 | false |
| string_one | 1 | 0 | false |
| decimal_one | 1 | 0 | false |
| exponent_one | 1 | 0 | false |
| explicit_null | 0 | 0 | false |
| boolean_true | 0 | 0 | false |
| negative_integer | -1 | 0 | false |
| integer_two | 2 | 0 | false |
| out_of_byte_range | 256 | 0 | false |
| duplicate_zero_one | 0 | 0 | false |
| duplicate_one_zero | 1 | 0 | false |
| expiry_missing | 1 | 0 | false |
| expiry_integer | 1 | 123 | false |
| expiry_string | 1 | 123 | false |
| expiry_decimal | 1 | 123 | false |
| expiry_null | 1 | 0 | false |
| expiry_negative | 1 | **4294967295** | false |
| logical_error_true | 0 | 0 | true |
| logical_error_text | 0 | 0 | true |

## Interpretation: important protocol behavior and historical limitation

1. **String and noninteger scalar coercion:** in Unity Editor 2021.3.45f1, JSON `{"servertype":"1"}`, `{"servertype":1.0}`, and `{"servertype":1e0}` all produced C# `int servertype=1` with both overloads. These inputs would **not** have been recorded by legacy JPCG PHP's `is_int($serverType)` check as integer `1` for the quoted-string or decimal/exponent forms. This provides a *plausible compatibility explanation* for the archived `servertype:null` diagnostic, but **does not identify the actual upstream JSON type**.
2. **Missing/null `servertype` defaults to 0:** both gave `0` in the synthetic Unity Editor fixture, consistent with the ordinary default for an integer. Since 0 labels LoginServer, assuming a missing type corresponds to a GameServer reply is unsafe.
3. **Duplicate key ambiguity:** the observed fixture retained the **first** `servertype` value for both reversed duplicate orders. CGM-owned provider response contracts must reject duplicate security-relevant JSON keys rather than rely on a particular parser's precedence.
4. **Unsigned wrapping:** JSON `vip_expiry:-1` was deserialized as `uint=4294967295`, a potentially very long-lived value if treated without range validation. Require explicit nonnegative bounded integer parsing in the trusted **server-side** contract; do not accept or synthesize malformed expiry inputs as valid authorization.
5. **Boolean string coercion:** JSON `{"error":"true"}` became `bool true` in the synthetic Editor fixture. This is a type-coercion observation, not proof of acceptance of other arbitrary string values or evidence of original upstream behavior.

**Critical scope:** This experiment used the standalone **Unity Editor**, not the original ARM64 IL2CPP Android player, not the original ANLGarden service, and not a real account login. The same Unity 2021.3 version and matching field types make it a useful compatibility clue, but platform/runtime and exact class differences remain possible. A successful-looking `MGRelayResponse` still does not prove authentic LoginServer/GameServer/Garden acceptance. Do **not** turn a VIP rejection into success or construct fabricated game/CCU/Garden sessions.

## Next bounded engineering step

Record this as the completed **Unity scalar-type checkpoint**. If designing the new *CGM-owned* typed response interface, perform strict key-presence, unique-key, explicit type, expiry bounds, stage/route binding and provider identity validation **before** dispatching to any approved processor. Preserve the strict separation between observed Unity coercion (what the client could parse) and provider authorization (what the client may legitimately use).

The missing actual upstream representation can only be established from a pre-existing permitted non-secret fixture or an authorized privacy-safe **type-only** observation. Do not collect credential-bearing query strings, unprotected JSON, session keys or official account secrets. Phase40CC/40CD's default unconfigured provider must continue to deny; no new official session issuer has been established.

## Environment unchanged

No change to `public/`, Render/Cloudflare, original signed APK, current Nox62033, rollback Nox62030 or the GitHub default branch. Source: [Phase 40CD static/offline checkpoint](PHASE40CD_PROVIDER_HANDOFF_AND_JSON_PROBE.md) and [Unity probe source](unity/CgmPhase40CDJsonProbe.cs).
