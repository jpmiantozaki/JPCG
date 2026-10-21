# Phase 40Y — Actual LoginServer response dispatch: Ghidra decompilation checkpoint

**Evidence:** User-supplied 480-line Ghidra decompilation `LoginServerManager$$OnPacketReceived(long param_1,long param_2)` at Ghidra `0x007A738C`; inspect original supplied file for full annotations. STATIC analysis only; do not assume any live test or APK patch.

## Confirmed code-level branch

```text
Packet.ReadPacket(inputData)
ReadWord(packet)                        // packet size/header, not result
cVar4 = ReadByte(packet)                  // first command byte
cVar5 = ReadByte(packet)                  // second command byte
if cVar4 == 0x01 && cVar5 == 0x01:
    bVar6 = ReadByte(packet)              // result/status
    if bVar6 == 0:
        ... parse multiple fields into AUServerInfo ...
        ReadDWord() and ReadSafeString()
        gameServerCount = ReadByte()
        loop(gameServerCount) with server list item fields
        ... read additional account/session fields ...
        Dispatcher.Invoke(b__41_2)       // may control UI; lambda body not yet examined
        GameServerManager.ConnectToGameServer()
        LoginServerManager.DisconnectFromLoginServer()
    elif bVar6 == 3:
        Dispatcher.Invoke(b__41_3)
        Dispatcher.Invoke(b__41_4)       // actual UI/error meaning unconfirmed
    elif bVar6 == 6:
        Dispatcher.Invoke(b__41_5)
        Dispatcher.Invoke(b__41_6)
    elif bVar6 == 14:
        Dispatcher.Invoke(b__41_7)
        Dispatcher.Invoke(b__41_8)
    else:
        Dispatcher.Invoke(b__41_9)
        Dispatcher.Invoke(b__10)
elif cVar4 == 0x01 and cVar5 == 0:
    read additional byte cVar4
    if cVar4 == 5: Dispatcher.Invoke(b__41_0)
    else: Dispatcher.Invoke(b__41_1)
else:
    Unity.Debug.Log(prefixed hex of unexpected packet) // avoid emitting raw packet values in diagnostics
```

**Evidence anchor ranges (from user pasted source):**
- Lines 61–89 packet parse, `01 01`, status 0.
- Lines 95–275 account/session/game server list parsing; field semantic names not resolved.
- Lines 276–300 `Dispatcher.Invoke(b__41_2)`, `GameServerManager.ConnectToGameServer`, `DisconnectFromLoginServer`.
- Lines 314–404 other status handlers (3, 6, 14, default) dispatched asynchronously to UI lambdas.
- Lines 406–439 `01 00` alternate response; third byte 5 dispatches b41_0, otherwise b41_1.
- Lines 450–473 other packet logging; warns raw hex logging could include sensitive data.
- Lines 475–477 resets `param_1+0x48`; not safe to infer semantic name from assignment alone.

## Meaning for CGM-owned membership integration

The **observable transition toward GameServer** occurs in original client code only after an appropriate LoginServer packet with successful status `0` and successful parsing. The target GameServer popup/UI behavior may also depend on `b__41_2`, which is not decompiled in this excerpt. A third-party upstream relay response `error=false` is a *different* layer than this original LoginServer status `bVar6==0`. Neither CGM membership ACTIVE nor an upstream relay success flag implies the latter.

Prior client Stage0 path `Login()` -> `SendLogin()` -> `RelayServerData()` -> accepted original LoginServer packet must remain unchanged if/when a CGM pre-check is added. Existing ANLGarden VIP rejection in the upstream relay remains a separate gate, not authorization to bypass by manipulating status packets.

## Next smallest read-only static check

Using original Ghidra project, navigate to **`Method$LoginServerManager.<OnPacketReceived>b__41_3()`** in listing/decompiler, using its method address from `dump.cs` / metadata XREF (do not guess RVA). Identify UI/error text and side effects for status 3. Then check **`b__41_9`/`b__10`** for fallback and **`b__41_2`** for connecting UI. Do not label status 3 'wrong password' without decompilation proof.

**Acceptance checkpoint:** report exact mapping of status values to UI/error callbacks from static code and acknowledge which remain unresolved. No live wrong-password tests, raw credential packet captures, replay, fabricated success response, or modifying backend/ANLGarden gating required.

## Phase 40Y follow-up — dump.cs and matching native ELF inspected directly (2026-10-21)

User noted `dump.cs` is already in Library. Retrieved Library `dump.cs` (matching also `dump(1).cs`) and `cgmlibil2cpp.so`. Offline host `sha256sum` for `cgmlibil2cpp.so` is `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`, matching previously confirmed CGM ARM64 library. No emulator access or live server traffic was needed.

Original `dump.cs` callback RVAs and Ghidra addresses (`+0x00100000`):

| Callback | ARM64 RVA | Ghidra VA | Direct native ELF finding |
|--|--|--|--|
| `LoginServerManager.<OnPacketReceived>b__41_2` | `0x6A8A80` | `0x007A8A80` | Reads a manager field at `+0x18` then tail-calls `PlayerPrefs.SetString(string,string)` at `0x10920A8`; **do not assume popup** |
| `LoginServerManager.<OnPacketReceived>b__41_3` | `0x6A8AD8` | `0x007A8AD8` | Direct unconditional branch `b 0x6A86B4` (`DisconnectFromServer`) |
| `LoginServerManager.<OnPacketReceived>b__41_5` | `0x6A8ADC` | `0x007A8ADC` | Direct unconditional branch `b 0x6A86B4` |
| `LoginServerManager.<OnPacketReceived>b__41_7` | `0x6A8AE0` | `0x007A8AE0` | Direct unconditional branch `b 0x6A86B4` |
| `LoginServerManager.<OnPacketReceived>b__41_8` | `0x6A8AE4` | `0x007A8AE4` | Formats/uses manager field at `+0x18` and calls `MessageBoxManager.ShowMessage` at `0x65662C` (exact message content unknown) |
| `LoginServerManager.<OnPacketReceived>b__41_9` | `0x6A8BA0` | `0x007A8BA0` | Direct unconditional branch `b 0x6A86B4` |
| `LoginServerManager.<>c.<OnPacketReceived>b__41_4` | `0x6A9610` | `0x007A9610` | Calls `MessageBoxManager.ShowMessage` on a metadata-defined string constant; exact content unknown |
| `LoginServerManager.<>c.<OnPacketReceived>b__41_6` | `0x6A968C` | `0x007A968C` | Calls `MessageBoxManager.ShowMessage` on another metadata-defined string constant |
| `LoginServerManager.<>c__DisplayClass41_0.<OnPacketReceived>b__1` | `0x6A9708` | `0x007A9708` | Reads `byErrorCode` at object `+0x10`, converts byte to string and shows it via `MessageBoxManager.ShowMessage` |
| `LoginServerManager.<>c__DisplayClass41_0.<OnPacketReceived>b__10` | `0x6A97B4` | `0x007A97B4` | Reads `byLoginCode` at object `+0x11`, converts byte to string and shows it via `MessageBoxManager.ShowMessage` |

`MessageBoxManager.ShowMessage(string,bool)` address is also checked against `dump.cs` at `0x65662C` and `PlayerPrefs.SetString` at `0x10920A8`. The native `DisconnectFromServer` method at `0x6A86B4` calls `ShowLoginButton(true)` at `0x6A5F20`, disables a UI object through `GameObject.SetActive(false)`, then `DisconnectFromLoginServer()`.

**Updated status handling grounded by user-supplied parent callback and exact ELF:**
- Login packet result `0`: saves account/session/game-server fields, triggers `b__41_2`, calls original `ConnectToGameServer`, disconnects LoginServer (normal handoff).
- Result `3`: `b__41_3 -> DisconnectFromServer`, `b__41_4 -> ShowMessage(literal)`. The literal is not yet resolved, so do not label `3` wrong-password error yet.
- Result `6`: `b__41_5 -> DisconnectFromServer`, `b__41_6 -> ShowMessage(other literal)`.
- Result `14`: `b__41_7 -> DisconnectFromServer`, `b__41_8 -> ShowMessage(dynamic manager text)`.
- Other results: `b__41_9 -> DisconnectFromServer`, `b__10 -> ShowMessage(numeric result)`.
- This is **official LoginServer packet result handling**, separate from upstream third-party `VIP` error at `RelayServerData` HTTP callback and CGM membership `authfirst.php`.

This checkpoint resolves the callback *addresses and native actions* without requiring the user to search `dump.cs` or take new screenshots. Remaining useful but narrower question is mapping string constants for result `3`/`6` to exact popup text; optional read-only metadata inspection can do this. No fabricated or bypassed server results.

## Confirmed user experience — saved email after successful login (2026-10-21)

User reports that the APK has a **Remember Email** checkbox. When checked, it saves the typed login email so that a later restart/reconnect autofills the email and prompts for the password again. This is **user-observed UI behavior**, not a decompiled proof of the precise checkbox conditional.

The compiled `LoginServerManager.<OnPacketReceived>b__41_2` at RVA `0x6A8A80` invokes `PlayerPrefs.SetString`, and parent `OnPacketReceived` dispatches this callback in the successful result-0 branch. Together with user observation, this supports persistence of the email/login identifier on a successful login, but the exact `PlayerPrefs` key and check-box branch need verifying before claiming a byte-for-byte mapping. There is no evidence here that the password is saved.

**Auth-First integration impact:** read the **currently displayed identifier** upon each user confirmation (typed or autofilled) and check fresh CGM membership against that value. A cached login ID is not proof of CGM ACTIVE or authenticated identity; preserve Remember Email UI and original password processing without assuming preauthorization from stored preferences. Don't substitute cached ID for edited UI content. No authentication bypass or upstream modification.
