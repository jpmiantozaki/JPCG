# CGM Phase 2.9 — Original native sender handoff (offline verified)

Date: 2026-10-28. User asked to stay on the original CGM APK endgame and disregard `garden_tool.py`. This checkpoint contains no deployment, game login, native hook, or APK installation.

## Original APK independently verified

Analyzed a previously uploaded `CGM-62031-Local-SIGNED.apk` read-only:

- APK SHA-256: `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`.
- Original ARM64 `libil2cpp.so` SHA-256: `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8` (matches archived BASE). `classes.dex` matches archived BASE.
- Original ordinary `Login()` branch at RVA `0x6A8868` calls original `SendLogin()` at `0x6A80C0`. `0x6A8870` writes original `isLoginProcess`; epilogue begins `0x6A8874`.
- Second web callback direct branch to `SendLogin` at RVA `0x6A8A20`, with web flag `w4=1`. It is unapproved until genuine web identity binding is shown.
- Crucial identity dataflow: `SendLogin` gets prepared account value from `x1 <- x20`. `x20` was created by method `0xCB6278`, recognized in the archived native name map as `System.String.Replace(string,string)`. This audit does not resolve replacement arguments, so it is **not safe** to equate raw UI email with the actual submitted/provider account identifier.
- Signed reference metadata targets `http://127.0.0.1:8091/` for five web PHP routes. This is Android guest localhost, not the public membership endpoint or an automatically reachable Windows port. No ADB connection/mapping was used here.

## Policy integration candidate (offline only)

- Reused Phase40BR's original `cgm_sender_guard.h/.c` unchanged.
- Added a NEW host-only handoff model that consumes one fresh identity-bound CGM ACTIVE permit before invoking an unchanged **synthetic sender**; deny leaves both sender and simulated original progress flag untouched; allow invokes sender once and then models the original post-call flag update.
- Result: **34/34** new C handoff assertions passed; **6/6** Python offline verifier tests passed using the archived APK. The read-only verifier checks exact ELF mappings/bytes/sha, both BL branch targets, and hard-stops on an unknown variant.
- Full 12-file package in the current conversation attachment: `CGM_Phase29_Original_Sender_Handoff_Offline_2026-10-28.zip`, SHA-256 `f8165ea0e43cf7400d862c466727347d92f29cb235ebb4404a69e8f9f8de7c23`. Original APK and native binary are **not redistributed** in the package.

## Preserved historical runtime evidence

Phase40BP user logs from Nox `127.0.0.1:62033` already demonstrated original APK CGM ACTIVE recognition at inner CONFIRM through the Java/JNI/Unity loader. Phase40BU deny path yielded conflicting earlier local observations; Phase40BV follow-up provided a bounded one-click no-session local witness. None proves a compatible full conditional native sender detour or accepted official authentication.

## Next engineering target

Identify and verify the actual `String.Replace` identity normalization operands and define the exact trust binding between a CGM member and the officially authenticated account. Then implement a documented or independently ABI-verified native sender-boundary handoff, preserving original `SendLogin` arguments and original PlayPark sessions: DENY must skip both send and progress flag; ALLOW must continue **unchanged** exactly once. Validate on isolated synthetic local fixtures before any explicitly authorized live login.

Independent PlayPark/mobile-relay session issuance and genuine GameServer/Garden acceptance remain unresolved; current JPCG `session.php` is a forwarding bridge. Membership ACTIVE does not override upstream entitlement. No files of `main` altered, no Nox/ADB/Frida touched; rollback `62030` untouched.
