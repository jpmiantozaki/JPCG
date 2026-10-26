# CGM Phase 40CA — Upstream clues from original Unity wiring and BASE response metadata

**Date:** 2026-10-26 (Asia/Manila)  
**Scope:** New read-only inspection of original user-supplied UnityFS bundle, original ARM64 ELF and previously supplied sanitized October 8 BASE relay diagnostics; targeted public-source search. No emulator, production endpoint, APK patch, account credentials or authenticated service request was used.

## Executive finding

The remaining session-processor dependency is now better localized. **The ordinary native login path is heavily linked to the HTTP `RelayWebManager.SendRelayInfo` route; neither the built-in TCP `RelayServerManager` nor the web/PIN login entry is demonstrated as a currently wired replacement.** A separate historical evidence limitation also emerged: all **20/20** application-positive BASE relay responses had `servertype: null` in our strict PHP *diagnostic*, which collapses missing and non-integer JSON values into the same null. The actual upstream value/type therefore remains **unknown** from those archived logs.

**No independent accepted game packet processor, verified third-party API or legitimate official session issuer was discovered in this checkpoint.** The goal is to narrow unknowns, not replace any upstream VIP decision or synthesize server success.

## 1. Exact source identities and method of analysis

| Source | Identity / scope |
|---|---|
| APK | `CGM-62031-Local-SIGNED.apk`; SHA-256 `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0` |
| ARM64 native ELF | `libil2cpp.so`; SHA-256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8` |
| UnityFS bundle | `assets/bin/Data/data.unity3d`; SHA-256 `20a94a63c13541311578887f226d76ef292e2e242cb0a0a4919da792967ac0ac` |
| UnityFS parsing | Verified UnityFS v8 header; **3,262** LZ4-compressed blocks; decompressed virtual data length **427,433,404** bytes; **13** virtual nodes including `level0`, `level1`, `globalgamemanagers.assets` and resource nodes. Blocks streamed read-only; searched both literal ASCII and UTF-16LE references across block boundaries. |
| Archived BASE diagnostics | Prior user-owned October 8 `Pasted text(3).txt` and `Pasted text(4).txt` under `cgm_trace_research`; only extracted previously sanitized `[CGM_SESSION_REQUEST]` and `[CGM_SESSION_RESPONSE]` JSON. Never copied raw `/session.php?data=` lines or account material into output. |
| Native branch scan | Direct AArch64 `B`/`BL` opcode-target scan over original `.plt`, `.text`, and `il2cpp` executable sections; validated via independently known 2x `SendLogin` and 3x Garden connect callsites. The scan **does not cover indirect, reflection/delegate or engine-invoked calls**. |

## 2. NEW CLUE A — Unity assets expose PIN UI methods but no persisted web-login entry

Fully decompressed virtual UnityFS search:

| Literal method or class name | Serialized occurrences | Location |
|---|---:|---|
| `ConnectToServer` | **2** | `level1` |
| `btnLogin` | **2** | `level1` |
| `SubmitSecondPin` | **1** | `level1` (`Pressed`/`Selected`/`Disabled` and `UnityEngine.Object` serialized context) |
| `CancelSecondPin` | **1** | `level1` |
| `WebLogin` | **0** | No literal across all extracted nodes |
| `LoadWebLogin` | **0** | No literal across all extracted nodes |
| `ConnectToRelayServer` | **0** | No literal across all extracted nodes |
| `SendRelayPacket` | **0** | No literal across all extracted nodes |
| `LoginWebManager` | **2** | `globalgamemanagers.assets` script/type metadata, **not** `level1` method binding |
| `PinManager` | **2** | `globalgamemanagers.assets` script/type metadata |

The `SubmitSecondPin` neighborhood contains ordinary Unity UI state names and a UnityEngine.Object signature, consistent with serialized event data; this is not a complete object-level UnityEvent reconstruction. The existence of `ConnectToServer` and PIN method names shows that the string scan actually sees method-like serialized references. **Absence of `WebLogin` is evidence against a direct persisted literal UnityEvent binding in this bundle, not proof that C# runtime code, indirect callbacks or a different asset cannot invoke it.**

Prior audited compiled chain remains real: `LoginWebManager.WebLogin` -> PIN checks -> `PinManager.FetchData` parses `AccountInfo.usersn` and `ccukey` -> writes legitimate `AUServerInfo` fields -> conditional Garden connect. These values must still originate from a trustworthy authenticated provider; no valid session was observed or issued here. See prior `CGM_Garden_Entry_Provenance_2026-10-24.md` and `CGM-AuthFirst-Test4-Report.txt`.

## 3. NEW CLUE B — Direct native calls favor the ordinary HTTP relay, not a discovered alternate TCP provider

All targets below are **original ELF RVAs** (not Ghidra-base-adjusted addresses).

| Function | Exact RVA | Direct executable B/BL calls |
|---|---:|---:|
| `RelayWebManager.SendRelayInfo` | `0x6A82AC` | **7** |
| `RelayWebManager.RelayServerData` | `0x6B04CC` | **1** |
| `LoginServerManager.SendLogin` | `0x6A80C0` | **2** (previously known, independent cross-check) |
| `GardenServerManager.ConnectToGardenServer` | `0x66CCE8` | **3** (previously known, independent cross-check) |
| `RelayServerManager.ConnectToRelayServer` | `0x6AE124` | **0** |
| `RelayServerManager.ConnectToRelayServerThread` | `0x6AE60C` | **0** |
| `RelayServerManager.SendRelayPacket` | `0x6AF880` | **0** |
| `RelayServerManager.SendRedemptionCode` | `0x6AF960` | **0** |
| `LoginWebManager.WebLogin` | `0x6AA188` | **0** |
| `PinManager.LoadSecondPin` | `0x6AA54C` | **2** (previously known) |

A different `RelayServerManager` class exists and has a TCP packet sender, but it is **not proved used by the yellow LOGIN -> CONFIRM -> HTTP `/session.php` route**. Zero direct calls combined with absent persistent method-name literals narrows it to a lower-priority alternate/dormant hypothesis. This does NOT rule out callbacks, delegates or reflection.

## 4. NEW CLUE C — Archived successful-looking BASE response metadata lost destination type

The two **unique** October 8 archive snippets contain **20** request/response diagnostic pairs from **three** historical BASE-origin clusters, not 20 independent logins. The **strict PHP diagnostic** returned:

| Diagnostic fact | Observed |
|---|---:|
| HTTP status `200` | **20/20** |
| Protected JSON `error=false` | **20/20** |
| Response `data_hex_valid=true` | **20/20** |
| `session_type=string`, `session_bytes=12` | **20/20** |
| **Diagnostic** `servertype=null` | **20/20** |
| Stage 0 replies | **3**, all `vip_expiry_present=false` |
| Stage 1 replies | **17**, all `vip_expiry_present=true`, `vip_expiry_positive=false` |

**Critical interpretation:** the staging PHP `_cgm_session_diagnostic.php` calculates:

```php
$record['servertype'] = is_int($serverType) && $serverType >= 0 && $serverType <= 255 ? $serverType : null;
```

Therefore a saved **diagnostic null** means only “the actual JSON field was not an integer between 0 and 255 when PHP decoded it.” It **does not distinguish** absent key, explicit null, string-encoded number, float, wrong type or invalid integer. No authentic decoded response body or raw server value is preserved by these redacted diagnostics. **Do not silently infer `servertype=0`, `1`, or omitted**, or infer that the native Unity app successfully processed every packet based on the metadata alone. Any future privacy-safe authorized diagnostic should record **only field-present boolean, JSON type category and whether a numeric value is in range**, without storing a payload or login materials.

The stage-specific `vip_expiry` presence distinction may point to divergent response serialization branches or application behavior, but it is **not** evidence of different upstream hosts, VIP policy or an official game authorization rule.

## 5. NEW CLUE D — The historic Stage 1 handler must tolerate overlapping HTTP requests

Two confirmed overlapping **Stage 1** request pairs in the same BASE-origin archive:

- At 13:33:08 UTC, 9-byte and 14-byte original packet requests arrived **before either preceding response finished**; responses were logged around 13:33:08.211 UTC.
- At 13:33:33 UTC, a 9-byte request and a 14-byte request likewise overlapped; responses followed around 13:33:33.400 and 13:33:33.415 UTC.

The input/output diagnostic rows distinguish these packet lengths without exposing content. Within this bounded archive the observed approximate maximum was **two** concurrent pending HTTP requests. Historical body timing is not a complete end-to-end latency trace and cannot prove identity binding or reply-token rotation. This reinforces the prior callback-ownership warning: stage alone, a packet counter that resets across socket generations, or one mutable relay manager string is not a sufficient global request identity.

## 6. Supporting framing clue — bounded, NOT a working upstream algorithm

A prior read-only native audit showed incoming Login/Game `PacketEncryption.Decrypt(byte[])` can remove a data-dependent **3–12-byte** outer overhead. Historical upstream response `data` lengths exceeded original outgoing frame lengths by **5–12 bytes**. The ranges are compatible as **lengths only**: the inspected decoder is applied to incoming game traffic; it is not an authenticated independent upstream encoder. The original `PacketEncryption` class declaration contains `Decrypt(byte[])` but no separate `Encrypt(byte[])` method. Normal Login/Game `Session.Send` uses send-mode 0, so a successful intermediary must supply correctly prepared bytes from elsewhere. Nothing in this test demonstrates an inverse transform or legitimate login/session issuance.

## 7. Public-source screening

Targeted public searches on 2026-10-26 for combinations of `ANLGarden`, `MGRelayResponse`, `RelayWebManager`, `session.php`, PlayPark Garden API, and `moveongame.com/appapi/gardenapi` found **no independently validated matching source implementation or documented delegated authentication contract**. Public community material says Mobile Garden is **not officially endorsed**, which is not a statement about protocol compatibility or individual usage permissions. A negative search result is not proof a compatible provider/API does not exist.

## Engineering decision and finite next steps

1. **Highest-value unresolved contract detail:** the real **JSON type/presence of `servertype`** in a legitimately authorized provider response. Our current historical *diagnostic* discards precisely the type distinction necessary to interpret the callback route. Add a **staging-only, type-only diagnostic** or inspect previously saved permissioned, non-secret fixtures; don't request raw credential-bearing URLs, decoded passwords or token strings.
2. **Do not treat web/PIN or TCP relay as discovered drop-in upstream replacements.** Prove direct authorized activation or provider contract before investing new APK builds in them. Neither route generates an official session by mere client execution.
3. Model multiple in-flight Stage 1 operations and per-request callback/connection ownership independently of relay session string; existing native audit's stale-callback hazard is source-supported but not runtime-reproduced.
4. Continue seeking a genuine, independently authorized session verifier/processor and a way to bind official account identity to CGM membership. No amount of shape validation or synthetic successful packet data creates PlayPark authorization.

### Safety and change boundary

All investigations were read-only, local and static/archived. No sensitive packets, credentials, relay session strings or live provider requests were published. No Render/Cloudflare environment, official server, GitHub production branch, original APK, experimental Nox62033 or rollback Nox62030 was modified. This report is an evidence checkpoint, **not** a working independent upstream implementation or official Game/Garden login proof.
## Phase 40CB erratum (2026-10-26)

**Correction:** the old `vip_expiry_present` diagnostic is `is_int($expiry)`, **not a JSON key-presence test**. Three Stage0 rows with `false` do not prove the upstream field was missing. Seventeen Stage1 rows with `true` mean PHP decoded an integer, and their `vip_expiry_positive=false` means nonpositive. Likewise all twenty `servertype=null` rows mean PHP did not decode an in-range integer, and conventional numeric JSON `0`/`1` was *not* logged. The actual JSON type remains unknown. See [Phase 40CB](phase40cb-original-upstream-response-type-contract-2026-10-26.md) for exact native Unity deserializer and type-only tests.
