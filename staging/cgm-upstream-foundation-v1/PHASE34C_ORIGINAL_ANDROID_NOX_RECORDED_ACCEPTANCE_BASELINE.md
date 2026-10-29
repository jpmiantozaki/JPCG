# Phase 3.4C — Original Android/Nox recorded Login → Game → Garden baseline

**Saved 2026-10-29 (Asia/Manila); read-only historical evidence.** This corrects a source-attribution error in Phases **3.4A/3.4B**: the older **Phase 4.24** research describes `test2.pcapng` as the **original PC** recording and `cgm(1).pcapng` as the **original Android/Nox** recording. Calling BOTH captures original PC was inaccurate. The original PCAPNG alone does not cryptographically prove the creating application/process/account.

## What is now demonstrated

Both user-owned captures contain one client network address and one TCP socket per service on LoginServer `18805`, GameServer `18806` and GardenServer `20840`. The correctly reassembled server-origin native response shapes appear in **the same temporal order as the original login flow**.

| Checkpoint (relative time from first LoginServer client packet) | Android/Nox-labeled | PC-labeled |
| --- | ---: | ---: |
| LoginServer C2S `0101` | 0.0000s | 0.0000s |
| LoginServer S2C opcode/subtype/result `01/01/00` | 0.0975s | 0.0857s |
| GameServer C2S VerifyCCU `0016` | 0.2808s | 0.1531s |
| GameServer C2S Game Logon `0100` | 0.4306s | 0.2528s |
| GameServer S2C `01/00/00` | 0.5613s | 0.4155s |
| First Garden C2S request | 8.0384s | 52.2249s |
| Garden S2C `C8/00/00` | 8.0866s | 52.2744s |
| Garden S2C `C8/02/00` | 8.3426s | 53.8648s |

**Android/Nox archive:** 27 Login/Game frames + 25 Garden frames = **52/52 decoded**. **PC archive:** 219 Login/Game + 36 Garden = **255/255 decoded**. No detected TCP gaps or leftover bytes in either reassembled recording. The PC archive contains **five stale one-byte overlap candidates** (conflicting single byte at the last already accepted sequence position). These append zero new bytes and were counted, not used as Garden payload. The reason for these segments is not independently determined.

Correct PCAPNG timestamp resolution is `if_tsresol=9` (nanoseconds). Previous microsecond interpretation of the native timestamp words would have made session timing 1000x too large.

## Original mobile native code verification

The exact existing `CGM-62031-Local-SIGNED.apk` SHA256 `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0` contains original ARM64 `libil2cpp.so` SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`. The original file passed **18 instruction-word checks and 12 direct call target checks**:

- `LoginServerManager.OnPacketReceived` RVA `0x6A738C`: command/subcommand `01/01` zero-result branch.
- `GameServerManager.Packet00_VerifyCCU` RVA `0x69AA08`: marker `0x16`, result `1`, original `SendLogon` call `0x69AAC4 -> 0x69B9D8`.
- `GameServerManager.Packet01_Login` RVA `0x69AB94`: two zero-result checks.
- `GameServerManager.Packet09_EnterLeaveChannel` RVA `0x69AD18`: marker `0x0A` and original `ConnectToGardenServer` direct call `0x69AE48 -> 0x66CCE8`.
- `GardenServerManager.GardenPacket0_Login` RVA `0x66E104` and `GardenPacket2_LoadGarden` RVA `0x66E548`: native result handling.
- Existing `GameServerManager.Packet10_RequestUserInfo` RVA `0x69AEB8` is still not a completely parsed success grammar.

These **static predicates** conceptually match the original recorded Login/Game/Garden reply shapes. They do NOT establish that the pinned APK emitted the archived capture or that a new CGM upstream supplied an accepted official session.

## Code, tests, and evidence

- Complete **read-only 3-port PCAPNG reconstruction with nanosecond timing, privacy-safe output, original PC mode-A and Garden mode-B decoding**, and the original ARM64 APK validator are shipped as the conversation ZIP `CGM_Phase34C_Original_Android_Recorded_Login_Chain_2026-10-29.zip`, SHA256 `0ce700ea5888619409c09c3dd7f52fa030ea1c27d48c1b64c0fb96afc7a6fb4d`. The ZIP contains no proprietary APK/ELF/PCAP, codebook tables, account credentials, session values or private endpoint addresses.
- Original capture audits and native-code checks were performed **offline**; **31/31 synthetic Python unit tests PASS** in the full ZIP, including source isolation, negative branch results, fake authorization rejection, TCP retransmission conflicts and explicit stale one-byte probe candidates.
- This GitHub branch stores only **sanitized aggregate archive metadata**, a strict metadata assertion policy, synthetic tests and this checkpoint. The metadata policy returns **false** for actual app-process attestation, official-provider authenticated identity, new CGM-owned mobile session and Garden authorization in every case. It never trusts an extra field that purports to declare a real session.
- The main JPCG `public/session.php` remains the original intermediary relay; Phase 3.0 `ProviderHandoffGate` stays deny-by-default without a trusted official provider/session issuer and account binding.

## Next Phase 3.4D — the real missing dependency

We have now established an historical original Android/Nox response chain, so **do not recapture an original login or redo Winsock/codec/Garden mapping** to prove that the pattern exists. What is **NOT** established is a new official mobile account/session for a **CGM ACTIVE-only** attempt. The existing `anlgarden.com` VIP denial remains authoritative for its own intermediary; it cannot be recast as CGM authorization.

A next valid GO must have a **permitted original official-provider session verification or intermediary-approved processing contract**, an independently verified same-account link to an exact ACTIVE CGM membership, a one-use login attempt/socket-generation correlation, and actual original-server Login/Game/Garden acknowledgments for that same new attempt. Without that permissioned provider dependency, use the existing Phase 4.28 original-PC passive Garden observer for read-only Garden state instead of fabricating credentials, CCU, VIP decisions or packet replies.

**Unchanged:** JPCG production/main, Render/Neon, original signed APK, Nox experimental `62033`, rollback `62030`, game servers and real user accounts.
