# CGM original client — Garden entry source paths (2026-10-24)

**Read-only original APK inspection.** No emulator, APK modifications, live game-server requests, credential processing, or production deployment. ARM64 `libil2cpp.so` verified SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`. Direct BL/B references scanned in both executable ELF sections; indirect/Unity delegate reachability not covered.

## All direct ConnectToGardenServer calls found

| Caller | Call site | Finding |
|---|---|---|
| `GameServerManager.Packet09_EnterLeaveChannel` `0x69AD18` | `0x69AE48` | Reads response byte at `0x69AD70`, checks value `0x0A`; reads subsequent byte, writes `AUServerInfo.userInfo.byHomePremium` at `+0x198/+0x19C`; calls Garden connect. |
| `PinManager.<FetchData>d__26.MoveNext` `0x6AD1E4` | `0x6ADAD0` | Distinct web/PIN account-data path conditionally starts Garden connect. Its real activation in normal BASE/MOD login not demonstrated. |
| `GardenManager.OnApplicationFocus(bool)` `0x63F140` | `0x63F218` | Garden already-loaded state at `+0x160`, app-focus, and other state gate a Garden re-connection. Not fresh authorization. |

## Critical new verified credential handoff

From original `dump.cs`, `AccountInfo.usersn` is `+0x20` and `AccountInfo.ccukey` is `+0x28`. Native `PinManager.FetchData.MoveNext` decrypts its account response (`0x6AD450`), calls `AccountInfo.CreateFromJSON` (`0x6AD474`), then converts `usersn` and stores it to `AUServerInfo.userInfo.dwUserSN` `+0x64/+0x74` (`0x6AD4DC-0x6AD514`), converts `ccukey` and stores it to `AUServerInfo.userInfo.dwUserCCU` `+0x78/+0x88` (`0x6AD544-0x6AD578`). This web/PIN code path is **now confirmed to populate the same Garden-login runtime fields**, beyond a signature-only hypothesis.

`GardenServerManager.OnConnectSuccess` `0x66D5F8` reads `AUServerInfo` user serial from `+0x64`, CCU from `+0x78`, and home premium from `+0x198`; invokes `SendGardenLogin` (`0x66D6DC`). `SendGardenLogin` `0x66D6F8` serializes these arguments to Garden TCP session (call to `Session.Send` at `0x66D7F4`). This **does not** directly read HTTP `MGRelayResponse.session` nor `PinManager.sessionKey` when preparing Garden login.

Normal BASE/MOD login previously mapped as authentic LoginServer responses populating user serial/CCU, GameServer CCU/logon continuation, and now a confirmed GameServer channel response triggering Garden. The alternative web/PIN path does **not** prove an externally supported provider, valid account verification, or working alternate Garden entry in current deployment. Do not interpret it as a bypass.


## Verified native web/PIN call chain (direct call-sites only)

- `LoginWebManager.WebLogin` region `0x6AA188-0x6AA32C` branches to `PinManager.LoadSecondPin` `0x6AA54C` at `0x6AA328`.
- Web-view callback region makes an additional call to `LoadSecondPin` at `0x6AAAEC`.
- `PinManager.SubmitSecondPin` calls `VerifyPin` at `0x6AC1A0` (VerifyPin wrapper RVA `0x6AC1C0`).
- `PinManager.<VerifyPin>d__25.MoveNext` calls `FetchData` at `0x6ACF60` (wrapper RVA `0x6AC250`).
- `PinManager.<FetchData>d__26.MoveNext` decodes account data, writes `userSN` and `CCU` to shared state, and calls Garden at `0x6ADAD0`.
- `LoginWebManager.WebLogin` has no direct B/BL caller in the scanned native code; Unity UI events or delegates can be indirect. **This compiled call chain does not prove the web login is reached during the user's normal yellow-login flow, or that its provider is functioning/authorized.**

These call-site facts were newly checked with a targeted original ARM64 binary cross-reference scan; they supplement but do not supersede the verified original LoginServer/GameServer path.

## Decision
- Preserve original APK-facing endpoints and normal LoginServer/GameServer/Garden procedure. CGM ACTIVE is **membership only**, not PlayPark identity authentication or a Garden credential.
- Treat mobile HTTP relay session token, web/PIN sessionKey and game/Garden userSN+CCU fields as distinct.
- Do not enable web/PIN route or issue fake userSN/CCU, game packets or session success. Only actual authorized service-issued credentials and official Garden acceptance can establish a valid session.
- Offline detailed report from this analysis is named `CGM_Garden_Entry_Provenance_2026-10-24.md` in the conversation artifact. No new user-side test required.
