# Phase 40BT — Exact original Login denial/continuation boundary

**Date:** 2026-10-25. **Status:** verified static ARM64 evidence only. **No APK rebuild, no live interception, no changes to official game authentication.**

## Why this was inspected

Phase 40BP verified, inside the original Android APK, that CGM ACTIVE can enable the original CONFIRM button. Phase 40BS then verified **one original `/session.php` HTTP request** reached a localhost-only one-shot fixture after normal UI interaction with a fictional password (`config=1, session=1, other=0`), and the helper restored the previous Nox reverse mapping. These results prove the ordinary UI admission and local submission path work together; they do **not** establish PlayPark authentication or a sender-level guard.

The next outstanding task is to enforce the CGM membership decision **before original native `SendLogin()` executes**, without modifying the real game packet/session behavior for a permitted attempt or leaving the UI stuck after denial.

## New static verification

The verified native file is the existing user-owned ARM64 `libil2cpp.so`:

- SHA-256: `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`
- Source: original CGM APK native module and the previously supplied IL2CPP declarations.
- Tools actually run in this checkpoint: bounded `llvm-objdump` and `verify_original_login_boundary.py` (read-only, exact instructions and full binary hash check).
- **17 instruction/call assertions passed.** This is a static check, not an Android hook test or a session-authentication test.

| Original native instruction | What the original code actually does |
|---|---|
| `0x6A8730` → `ldrb w8, [x19,#0x48]` | Reads `isLoginProcess` |
| `0x6A8734` → `cbnz w8, 0x6A8874` | Already-busy login returns via the existing epilogue |
| `0x6A8738` → `ldrb w8, [x19,#0x4A]` | Reads `isLoginSuccess` |
| `0x6A873C` → `cbnz w8, 0x6A8874` | Already-successful login uses the same epilogue |
| `0x6A8858..0x6A8864` | Prepares existing manager/username/password/version args and `fbLogin=0` |
| `0x6A8868` → `bl 0x6A80C0` | Original ordinary PlayPark `SendLogin()` call |
| `0x6A886C..0x6A8870` | **Sets `isLoginProcess = 1` immediately after the send call** |
| `0x6A8874..0x6A8880` | Restores saved registers/stack and returns, with no further flag write |
| `0x6A815C` inside `SendLogin` | Calls **mutating** packet-counter getter at `0x6BD3A8` |

The important new precision: **`0x6A8874` is not an invented exit; the original `Login()` itself uses this epilogue on its early guards**. A future denied login continuation must bypass **both** `SendLogin()` and the `isLoginProcess` write, and return with the correct preserved register/stack state. This identifies a plausible behavioral requirement; it is **not** proof that an inserted jump or new trampoline would be ABI-safe on Nox/Android.

**Additional caveat:** `Login()` can call `ConnectToLoginServer` earlier at `0x6A877C` when the manager has no connection. Thus denying at the late send boundary prevents the credential packet and counter mutation, **not necessarily all earlier transport setup**. A production-style gate should ideally prevent unnecessary connection attempts at the pre-submit policy boundary, while still protecting actual native sender entry.

## Guard behavior required for the next implementation

1. The existing CGM HTTPS reply must be strictly validated and associated with a fresh attempt generation and the **same original submitted account**. The visible CONFIRM state is not evidence sufficient for a native sender.
2. On denial: no `SendLogin`, no `Session.get_GetPacketCounter`, and no `isLoginProcess=1`; preserve retry/cancel state. Do not fake an HTTP success or a game session.
3. On allow: consume the approval once and continue through the **unaltered original** credential writer and session transport. Do not replace/transform credentials, official game replies or Garden state.
4. Match the real function calling convention, register/stack preservation, IL2CPP managed string lifetime and Android NativeBridge translated execution before any patch. The working Unity Java/JNI bridge and toy hook tests do not prove a safe new detour into original `Login()`.
5. Fail closed for unsupported legacy Facebook/web callbacks. The user confirmed ordinary PlayPark login is now the relevant route.
6. Keep any future staging APK on experimental Nox `127.0.0.1:62033`. Never target rollback `62030`; do not send real credentials into the existing local synthetic PHP/TCP fixture.

## Explicit project boundary

This client-side enforcement milestone is *separate* from the still-missing independent CGM upstream game/session processor. A valid `CGM ACTIVE` decision makes a CGM member eligible to attempt the untouched PlayPark login; it **does not** prove a separate ANL/BASE intermediary will accept a Stage 0 `VIP` check, and does not issue CCU/userSN or official Garden authorization.

## Checkpoint results

```text
PHASE40BS_CONFIG_REQUEST_SEEN
PHASE40BS_SESSION_ENDPOINT_REQUEST_SEEN count=1
PHASE40BS_ONE_SESSION_REQUEST_OBSERVED (NOT GAME_AUTH)
PHASE40BS_COUNTS config=1 session=1 other=0
PHASE40BS_ORIGINAL_CONFIGURATION_ROUTE_RESTORED
PHASE40BT_LOGIN_DENY_EPILOGUE_STATIC_VERIFIED
```

No additional Nox test is required by this static checkpoint. The next actual APK milestone is a **verified** sender interception with a denied-attempt/no-packet/no-progress-flag runtime test, followed by a permitted path unchanged through the same local fixture. Do not claim this exists until native integration and live evidence are obtained.