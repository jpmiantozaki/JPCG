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
