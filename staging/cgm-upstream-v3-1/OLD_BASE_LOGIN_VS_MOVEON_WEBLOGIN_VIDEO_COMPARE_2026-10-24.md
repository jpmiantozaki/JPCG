# CGM 1026: two historical login videos + Unity Editor screenshot — visual-only comparison

**Date:** 2026-10-24. **Sources:** User-uploaded screenshot of a historical Unity editor and two historical video demonstrations. Frame sampled at 2.5-s intervals across both clips, plus sub-second sampling around login/PIN transitions. Video A ~45.2 s, 400x234; Video B ~60.8 s, 400x184. No account credentials, login identities, tokens or PIN contents are reproduced in this report.

## Video A: MoveOn Audition web-login/PIN route (historical demonstration)
- ~0 s opening screen with **Login** button; ~2.5–10 s web-login page with login form and Facebook option.
- ~10.5–18 s **Input PIN** numeric keypad. The video's first PIN submission is around 17.5–18 s.
- ~18 s **Verifying PIN...**; transition to Couple Garden loading ~18.5–19.5 s and interactive Garden ~20 s onward.
- Later Garden-market interactions show successful in-video UI usage.
- No verified network trace or provider-issued authorization is available. The video does not prove that this legacy service is currently available or compatible with unchanged BASE/MOD 1026. It is consistent with compiled `LoginWebManager` / `PinManager.VerifyPin` / `FetchData` code, but the video itself does not prove exact build equivalence.

## Video B: old BASE/CGM regular credential-login route (historical demonstration identified by user)
- ~0–2.5 s **Login ID / Password / CONFIRM** screen. This video does not show a preceding Yellow Login start.
- ~2.75–3.1 s explicitly **Connecting to Game Server...**.
- ~3.2–3.4 s explicitly **Connecting to Garden Server...**.
- ~3.5–5.0 s **Couple Garden** loading, Garden interactive afterward, many menu/use actions through ~60 s.
- No second PIN screen visible between CONFIRM and Garden in this recording.
- No VIP decision dialog or explicit message is shown. This **does not prove a VIP check was absent**; it may happen silently before game continuation. Nor is network authentication proven solely by UI.
- Strong visual evidence for **normal credential-confirm -> GameServer UI -> GardenServer UI -> Garden** path, with PIN not mandatory for this particular demo.

## Screenshot: historical Unity editor
- Title bar: Unity 2018.2.9f1 Personal (64-bit); scene `GardenUI.unity` in `Garden Mobile` project, Android target.
- Hierarchy includes `Canvas` (with `Login`) and `Server Manager` / `Garden Manager` objects.
- Game view depicts `Login ID`, `Password`, `CONFIRM`, `CANCEL`, `SIGN-UP`, a `Login via Facebook` button, plus **Connecting to Login Server...** overlay.
- This indicates those UI elements were present in a Unity Editor project in the screenshot. **It is not a Unity project file, proof that source is available, or proof of original server-side `session.php` source or accepted GameServer packets.** It is not an actual live trace of the displayed transition timing.

## Evidence comparison and integration decision
1. Distinguish two branches: ordinary BASE/CGM credential login (**no PIN visible**) versus legacy MoveOn web-login (**PIN visible**).
2. The original normal request pipeline already mapped in compiled code is `LoginServerManager.SendLogin` -> `RelayWebManager.SendRelayInfo` -> HTTP `session.php` stage0; normal GameServer continuation sends stage1; request vs response `servertype` equality remains unverified.
3. Normal Garden entry reads game-authenticated `AUServerInfo.userInfo` userSN / CCU and connects to GardenServer; prior native research found GameServer Packet09 triggering Garden. The web/PIN route can separately populate those fields but is not needed for every normal login.
4. Optional second PIN remains **out of scope** for immediate CGM ACTIVE integration; preserve user-selected optional handling if legitimate provider supports it.
5. Keep CGM membership eligibility distinct from genuine official game/Garden authentication. Video evidence alone does not supply upstream packet processor, validate credentials, establish replayable tokens, or prove CGM-only login.
6. If a legitimately available, authorized Unity project source appears, compare exactly its `GardenUI` bindings and managed/native login call chain. It still would not independently reproduce the third-party `session.php` processing implementation.

**Next useful step:** use old BASE/CGM UI progression as a **behavioral acceptance checklist**: (a) legitimate login submit, (b) CGM ACTIVE admission, (c) original game-server acceptance, (d) original Garden connection, (e) optional PIN only when enabled. The still-missing prerequisite is a genuinely compatible and permitted session processor/identity proof—not reopening the optional PIN investigation. No live contact, code changes, APK builds or deployments.
