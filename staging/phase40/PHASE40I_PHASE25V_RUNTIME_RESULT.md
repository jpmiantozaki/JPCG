# Phase 40I — Phase 25V actual Nox runtime result

Source: user-supplied `phase25v-nox-ownership(1).json`, inspected 2026-10-21.
Diagnostic target: experimental Nox `127.0.0.1:62031`.
This note records the results only. It does **not** contain raw app credentials, raw protected network packets, or session secrets.

## Run result (direct JSON evidence)

| Top-level result | Value |
|---|---|
| `phase` | `2.5V` |
| `runtimeExecuted` | `true` |
| `probeChecksPassed` | `true` |
| `diagnosticCollectionPassed` | `true` |
| `methodHandleSelectionPassed` | `true` |
| `ownershipResolved` | `false` |
| `cleanupPassed` | `true` |
| `interceptionVerified` | `false` |
| `authFirstEnforced` | `false` |
| `gameCodeModified` | `false` |
| `gardenAccessVerified` | `false` |

One active runtime sample: `frames=11`, `failure=0`, Unity thread, IL2CPP thread, dispatcher, and login manager verified, identifier BLANK, `resolvedExportCount=37`, `loginMethodContractVerified=true`, `sendLoginMethodContractVerified=true`, `candidateProloguesMatch=true`, `loginHookInstalled=false`.

For both `Login()` and `SendLogin(...)`:
- Target method metadata uniquely matches the expected method.
- `get_MethodHandle` resolution has `state=unique-most-derived-compatible-override`, `selectionRule=nearest-concrete-compatible-override`, `preferredCompatibleMatches=1`, `shadowedCompatibleMatches=1`. This *fixes the prior Phase U inherited-method ambiguity*.
- `System.RuntimeMethodHandle` plus its enumerated superclasses yield `functionPointerGetter.state=method-name-absent`, `complete=true`, `nameMatches=0`, `selectedMethodIndex=-1`. The sought `GetFunctionPointer` is **absent** in the enumerated runtime metadata; it did not throw an unsupported exception.
- `nativeAddress=null`, `nativeAddressSource=unresolved`, `ownershipVerified=false`.
- The independent original-file ELF-segment/prologue checks pass (`candidateImage.verified=true`) for *candidate* addresses, but do not prove the managed method's actual runtime code pointer. Do not infer that a method handle Value is an executable pointer or use RVA matches as proof of native ownership.

During observation, `ownedHandlesCreated=49` and `ownedHandlesFreed=47` because handles were live. **At final drain**, `ownedHandlesCreated=53`, `ownedHandlesFreed=53`, `cleanupVerified=true`, `stopped=true`, `failure=0`. The post-drain manager/thread observer fields are naturally false after stopping and are not an active-run failure.

## Engineering decision

**Close the Phase V reflection-derived `GetFunctionPointer` path for this runtime.** Re-running identical S/T/U/V fixtures or the same Nox probe without a materially different supported mechanism will not resolve this specific missing API.

Potential independent evidence for a replacement route: matching IL2CPP generated build/linker symbols or application-owned source / supported extension with an explicit pre-submit callback and unchanged Login continuation. If another technique is considered, it requires an independently verifiable method ownership/call-coverage mechanism, tested first in an isolated benign fixture, not an unverified live Login/SendLogin inline patch. Never assume `RuntimeMethodHandle.Value` is the method's ARM64 entrypoint.

No authentic PlayPark login, GameServer/GardenServer session, or independent session provider is established by this diagnostic. The separate mobile session-relay dependency remains unresolved.

Preserve existing app and rollback emulator (`127.0.0.1:62030` untouched). No production backend or gateway changes.
