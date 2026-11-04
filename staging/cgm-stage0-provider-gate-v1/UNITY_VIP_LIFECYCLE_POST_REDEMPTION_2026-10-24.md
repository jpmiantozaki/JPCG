# Unity 1026 VIP response lifecycle — native read-only follow-up (2026-10-24)

**Investigation type:** Direct targeted ARM64 disassembly from original shared BASE/MOD CGM `libil2cpp.so`, SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`; method/field names matched to previously recovered `dump.cs`. Read only. No live server or emulator touched.

## Newly confirmed native code paths

### A. Stage0/1 `session.php` response: `RelayWebManager.<RelayServerData>b__11_0`, RVA `0x6B088C`

- Original protected reply is decrypted at `0x6B093C` and parsed via `MGRelayResponse.CreateFromJSON` at `0x6B0960`.
- On `error=true`, at `0x6B0980` it calls `RelayWebManager.DisconnectFromServer()`, RVA `0x6B07B4`; that routine directly calls **both** `LoginServerManager.DisconnectFromServer()` (RVA `0x6A86B4`) at `0x6B07F0` and `GameServerManager.DisconnectFromGameServer()` (RVA `0x699BB4`) at `0x6B0834`.
- When the error message is exactly `VIP`, it calls `VIPManager.ReceiveNoVIPData` (RVA `0x6B8AEC`) at `0x6B0A1C`. That method displays the VIP panel, preserves the original prefixed login ID in `szUserNameVIP`, and only strips the 5-character namespace for the display label. A denial cannot be assumed to leave the prior LoginServer/GameServer sessions connected.
- On `error=false`, callback stores `MGRelayResponse.session` in `RelayWebManager.session` at `0x6B0ACC`–`0x6B0AD0`. If `vip_expiry` is nonzero, converts it and stores into shared `AUServerInfo.userInfo.vipExp` at `0x6B0B08`–`0x6B0B30`, before later forwarding response packet according to the response's `servertype`. This is *provider relay expiry* and should not automatically be treated as authoritative CGM expiry.

### B. `redeemvip.php` response: `RelayWebManager.<>c.<RelayRedeemVIPData>b__10_0`, RVA `0x6B11A0`

- Decrypts original protected reply at `0x6B1230`, parses `MGRelayResponse` at `0x6B1254`.
- On `error=false`, calls `VIPManager.ShowVIP(false)` at `0x6B12A8`. The `ShowVIP` method at RVA `0x6B877C` toggles the VIP object and resets input fields / `bVIPMode` when hiding; it has **no direct call** to original `LoginServerManager.Login` or `SendLogin`.
- Success callback also writes `MGRelayResponse.vip_expiry` to the same shared `AUServerInfo.userInfo.vipExp` field at `0x6B12E4`–`0x6B130C` and displays original reply's `message` at `0x6B1348` via UI `ShowMessage`.
- On `error=true`, shows error/reply message via UI; no login resubmission.
- **No direct `Login()`, `SendLogin()`, `ConnectToServer()`, or provider-issued game packet path in this redemption response handler.** A separate user action or other application event is needed to re-establish an official login after earlier VIP rejection. Serialized Unity events might have additional behaviors, so this is a callback-local negative proof, not a claim about every possible app event.

### C. Later `verify.php` response: `RelayWebManager.<>c__DisplayClass9_0.<RelayVerifyVIP>b__0`, RVA `0x6B0D50`

- On `error=true` (at `0x6B0E24`), calls `LoginServerManager.DisconnectFromServer` at `0x6B0E60` and `GardenServerManager.DisconnectFromGardenServer` (RVA `0x66C954`) at `0x6B0EC0`. Calls app `RestoreLogin` (RVA `0x64277C`) at `0x6B0F00`, then passes stored/recovered username to `VIPManager.ReceiveNoVIPData` at `0x6B0F44`.
- On `error=false` (at `0x6B0F48`), it does **not** directly issue a LoginServer, GameServer or GardenServer auth command, and does **not** load `MGRelayResponse.vip_expiry` at offset `+0x38`. The visible positive branch does UI/message handling. It is a membership-related check, not an original game-session issuer.
- Unlike the Stage0 relay error handler, its explicit disconnect targets are LoginServer + GardenServer, not LoginServer + GameServer; preserve this distinction.

## Practical consequences for CGM development

1. **Distinct operations:** code redemption, membership verification and actual official LoginServer/GameServer authorization are separate client actions and responses. Neither `redeemvip.php` nor `verify.php` success proves an accepted game session.
2. **No automatic resume assumption:** Stage0 VIP rejection disconnects LoginServer/GameServer. A successful redemption callback closes VIP UI but does not by itself call `SendLogin`; avoid implementing a stage0 'resume original packet' inside a denial-only response handler.
3. **Expiry overwrite is concrete:** Both Stage0/1 original `session.php` positive replies and CGM redemption can write the same `AUServerInfo.userInfo.vipExp`. Original provider's reply may overwrite a prior CGM expiry on subsequent login; the positive `verify.php` callback doesn't correct this. Keep CGM membership expiry as a separately owned entitlement and display it through a properly designed, authenticated CGM UI if desired, rather than editing official game sessions.
4. **Client-compatible negative shape:** Staging `NativeVipDenialReply.php` can encode `error=true,message=VIP` to open original VIP UI and deliberately **must not** claim a valid authenticated game session. This checkpoint does not integrate it into `public/session.php`.
5. **Identity matching:** Prefixed `INPP.`/`INFB.` shared login ID and redemption ID remain exact/case-preserved; historical plain-email aliases must not be silently granted.

## Scope and status

This adds targeted callback-local reachability and state-mutation evidence. It is not a server-side `session.php` implementation. **No successful CGM-only authenticated upstream has been identified.** No new synthetic pass count claimed: existing Windows-confirmed 38/38, 34/34, 75/75 and 66/66 remain historical baselines; new findings are based on native disassembly rather than tests.

**No APK changes, production/JPCG endpoint changes, Nox or live PlayPark requests.** Next high-information step: determine whether any legitimate app UI action after redemption re-invokes Yellow Login/credential submission and document origin-accepted Stage0/Stage1 session continuity if authorized evidence becomes available. Never infer official authentication from success-shaped JSON.