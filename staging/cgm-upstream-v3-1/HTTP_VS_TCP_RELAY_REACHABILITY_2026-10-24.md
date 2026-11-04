# CGM / ANLGarden 1026 — Original HTTP vs TCP relay reachability
**Date:** 2026-10-24. **Outcome:** confirmed direct CALL/B sites prove normal login and game requests use HTTP relay; the distinct TCP relay is not demonstrated as a replacement for original `session.php`.

## Scope and evidence

Direct read-only static analysis of locally mounted original 1026 `lib/arm64-v8a/libil2cpp.so` at `/mnt/data/work_cgm_stage_type/libil2cpp.so`. SHA256 **fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8**, 27,093,976 bytes, the same ARM64 original previously compared between BASE and MOD.

AArch64 direct **BL** and unconditional **B** references were enumerated with 26-bit signed PC-relative branch decoding in both executable ELF sections: `.text` `0x3E1630-0x5E4FC0` and `il2cpp` `0x5E4FC0-0x127AEA0`. This is a direct-branch cross-reference scan, **not** a complete dynamic/callgraph analysis: it excludes indirect branches, reflection, Unity serialized callbacks and delegate invocations. The ELF was not patched; no traffic, account access or APK modifications.

## Native direct call sites

| Destination | RVA | Direct B/BL count | Refs and interpretation |
|---|---:|---:|---|
| `RelayWebManager.SendRelayInfo` | `0x6A82AC` | **7** | `0x69A6B8`, `0x69B9C4`, `0x69BB8C`, `0x69BCB4`, `0x69BDC4`, `0x69BEF4`, `0x6A82A4` |
| `RelayWebManager.RelayServerData` | `0x6B04CC` | **1** | `0x6A8448` from `SendRelayInfo` |
| `RelayServerManager.SendRelayPacket` | `0x6AF880` | **0** | No direct B/BL reference in scanned sections |
| `RelayServerManager.ConnectToRelayServer` | `0x6AE124` | **0** | No direct B/BL reference |
| `RelayServerManager.ConnectToRelayServerThread` | `0x6AE60C` | **0** | No direct B/BL reference |
| `RelayServerManager.SendRedemptionCode` | `0x6AF960` | **0** | No direct B/BL reference |
| `RelayServerManager.Start` | `0x6AE088` | **0** | Unity lifecycle methods are typically invoked through its engine, not direct native `bl` sites; absence is not proof of inactivity |
| `Session.Send` | `0x6BE850` | **56** | Numerous native packet senders; confirms cross-reference scanner sees ordinary call sites |

The normal login call at `0x6A829C` moves zero into `w2` (stage 0), and `0x6A82A4` directly branches to `SendRelayInfo`. Its body at `0x6A8448` directly calls `RelayServerData` `0x6B04CC`. Previously verified game caller locations set stage 1 and call the same HTTP path. This is an original-client code path, not a newly generated PHP implementation.

## TCP alternative investigation

`RelayServerManager` has an independently compiled `ConnectToRelayServer` method (`0x6AE124`) which constructs a separate `Session`, installs callbacks and calls `Session.Connect` (`0x6BDE4C`) using configured relay endpoint fields. The standalone `SendRelayPacket` (`0x6AF880`) constructs [16-bit frame size, opcode `3`, server type, packet data] and passes it to `Session.Send`. This TCP wrapper is **not evidence** of the HTTP `session.php` processor's successful packet adaptation algorithm.

No direct B/BL caller to the TCP `ConnectToRelayServer` or `SendRelayPacket` was located, while the normal LoginServer and GameServer senders directly call the HTTP relay. The TCP manager **might still be called by Unity events/delegates/indirect dispatch**; static negative evidence does not prove it never runs. Its configured IP/port also do not demonstrate a currently available compatible or authorized relay service.

## Engineering decision

**NO-GO on substituting the TCP relay for HTTP `session.php` in the unmodified APK based solely on the presence of `RelayServerManager`.** Existing login is proven to use `RelayWebManager.SendRelayInfo`. Switch would require changed client behavior and a compatible TCP relay service, neither established here.

Continue to preserve original five HTTP endpoint contracts; no new APK build, broad Ghidra loop, dynamic instrumentation, or synthetic forged success. An independent CGM mobile processor still needs legitimate game-compatible packet preparation, relay-session continuity and original game-service acceptance. The previous stage/type equality guard remains a staging-only additional policy, not an original-client validation.

## Next meaningful evidence

A real source-controlled `session.php` processor used by the original compatible mobile integration (with permission and safe, non-sensitive test vectors), or supported documented provider authentication/session interface. Until then, an independent 'success' implementation cannot be inferred by scanning dormant alternative client transport methods. This targeted reachability question is now closed.

**No production changes or requests were made.**
