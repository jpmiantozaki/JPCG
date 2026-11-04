# CGM 1026 — Direct ARM64 request-stage / response-dispatch audit

**Date:** 2026-10-24. **Method:** read-only disassembly of locally mounted original CGM Android ELF using llvm-objdump. SHA256 of examined `lib/arm64-v8a/libil2cpp.so`:
`fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`, size 27,093,976. This matches previously verified original BASE/MOD ARM64 executable. No native modifications, live traffic, APK rebuild or credentials.

## Confirmed original request-side stage values

In AArch64, the third argument to `RelayWebManager.SendRelayInfo(byte[],ServerType)` at RVA `0x6A82AC` is register `w2`.

| Method | ARM64 call site and evidence | Actual request stage |
|---|---|---|
| `LoginServerManager.SendLogin` `0x6A80C0` | `0x6A829C: mov w2, wzr`; `0x6A82A4: b 0x6A82AC` | **0** |
| `GameServerManager.SendVerifyCCU` `0x69A504` | `0x69A6AC: mov w2, #1`; `0x69A6B8: bl 0x6A82AC` | **1** |
| `GameServerManager.SendLogon` `0x69B9D8` | `0x69BB80: mov w2, #1`; `0x69BB8C: bl 0x6A82AC` | **1** |
| A further game request branch (within `0x69BBA4`) | `0x69BCA4: mov w2, #1`; `0x69BCB4: b 0x6A82AC` | **1** |

These call sites **confirm the request-side stage mapping** for these native methods, independently of previous dumps/chat notes. It does not prove every conceivable app call site or remote response mapping.

## Independently confirmed response-side dispatch

Original `RelayWebManager.<RelayServerData>b__11_0` at `0x6B088C` decrypts SecurePacket at `0x6B093C: bl 0x6B9130` and parses MGRelayResponse at `0x6B0960: bl 0x6B0084`.

Later callback code:
- `0x6B0B5C: ldr w8, [x19, #0x28]` — reads response `servertype`, offset verified previously by IL2CPP metadata.
- `0x6B0B64: cbz w8, 0x6B0C14` — **response type 0**; `0x6B0C5C: bl 0x6BE850` → LoginServer `Session.Send`.
- `0x6B0B68: cmp w8, #1` / `0x6B0B6C: b.ne 0x6B0C00` — type 1 otherwise branch away; `0x6B0BB0: bl 0x69BF08` → `GameServerManager.SendPacket`.
- The callback is routed by **response `servertype`**, **not** by a freshly checked comparison to the previous request stage. This is not evidence that actual backend positive replies ever use a cross-stage type.

## Consequence for staging v3.1 equality guard

The v3.1 candidate contains:
```php
if ($reply['servertype'] !== $stage) return $reject('RESPONSE_TYPE_MISMATCH');
```

Now qualified: **stage 0 requests ordinarily target LoginServer and stage 1 game continuation requests ordinarily target GameServer** are supported directly by client binary. However, **exact equality in every genuine `MGRelayResponse` is not established**: stage is request input, type is a separately supplied response field, and original client callback itself does not enforce equality. The v3.1 guard is an *additional staging policy* and may be overly restrictive against an unknown valid intermediary behavior.

**Deployment decision: NO-GO for enabling v3.1 strict equality until a permitted origin-attributed successful response confirms this mapping**. Do not remove the guard just to force through a real login, and do not infer equal field values from synthetic test passes. No live endpoint is being changed.

## Future evidence already supported by current diagnostics — no new scanner required

`public/_cgm_session_diagnostic.php` includes `request_stage`, `upstream_host`, HTTP `status`, `error`, `servertype`, `data_hex_valid`, `data_packet_bytes`, and `session_bytes` in redacted logs. On a legitimate, authorized successful test, these *metadata-only* fields would establish whether response type actually equals request stage; no packets, usernames, passwords, token contents, encrypted `data` query or raw URL should be exported or stored. The earlier four archived records had missing type and origin, and cannot resolve this rule.

## Main independent-upstream blocker unchanged

This clarifies a preexisting staging safety/compatibility assumption; it **does not reveal** how BASE/MOD server-side `session.php` prepares valid `MGRelayResponse.data`, generates relay sessions, or allows CGM ACTIVE-only members through the original game and Garden authentication. A successful independent processor still requires legitimately compatible server behavior and original game-service acceptance. Original APK, session routes and production left untouched.

**Sources:** locally mounted original signed CGM APK (read-only ELF); existing JPCG `public/_cgm_session_diagnostic.php`; staging v3.1 archive `CGM_Upstream_PacketBoundary_v3_1.zip` SHA256 `55abc6499cd7e88b9b3d8c758a499ae6b79558b2d987375aba36cb12f579f108`.
