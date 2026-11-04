# CGM Phase 40CE — Original Login/Game/Garden Acceptance Observation

**Date:** October 26, 2026 (Asia/Manila)  
**Status:** Completed offline implementation and user-provided archive audit; **no live official login** or non-VIP third-party intermediary bypass.

## Decision

Temporarily excluding **CGM's own** membership requirement from an *offline diagnostic observer* is appropriate: it lets us study the native official response chain independently of our membership policy. It **does not** disable or override the original mobile intermediary's VIP verification, issue any legitimate session, or make an inactive account eligible for PlayPark. The historical BASE-origin relay used an independently accepted VIP/account context; a CGM ACTIVE-only account was historically rejected by the original intermediary with `VIP` before the official server reply stage. Disabling the APK's later VIP UI callback alone would not make server authentication succeed.

## Existing code evidence independently reanchored

Rehashed the original uploaded APK SHA256 `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0` and its ARM64 `libil2cpp.so` SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`; reviewed the Oct 25 bounded native source audits and callback/lifetime evidence.

The original normal flow distinguishes these stages (ELF RVAs):

| Step | Native response-handler evidence | Exact interpretation |
|---|---|---|
| LoginServer | `OnPacketReceived` `0x6A738C`, result-zero success-style `[01,01]`, parsed server account/connection data | Original game LoginServer branch, stronger than HTTP success |
| VerifyCCU | `Packet00_VerifyCCU` `0x69AA08`, marker 0x16/result 1, then `SendLogon` | GameServer response branch, not Garden |
| Game login | `Packet01_Login` `0x69AB94`, result pair 0/0, then user-info request | GameServer login-response branch |
| User-info | `Packet10_RequestUserInfo` `0x69AEB8` | Handler exists; full success-result grammar not resolved |
| Channel | `Packet09_EnterLeaveChannel` `0x69AD18`, marker 0x0A conditional Garden-connect branch | Reaches Garden socket attempt after real game response |
| Garden login | `GardenPacket0_Login` `0x66E104`, result 0 writes balances and sends couple/time requests | Native Garden login-response continuation, **not yet data-loaded** |
| Garden data | `GardenPacket2_LoadGarden` `0x66E548`, result 0 populates Garden data and queues UI callbacks | Distinct Garden data-response continuation |
| UI Garden loaded | `GardenManager.LoadGarden` `0x640EF0`, flag `isGardenLoaded` at `0x641260` | Client state only; the method calls async VIP verification at `0x640FB8` before setting loaded |

No actual newly captured inbound server responses were claimed in this phase; these are static source-based branch observations.

## New tool — privacy-safe Phase40CE acceptance observer

Created `CGM_Phase40CE_Official_Acceptance_Observer_OFFLINE.zip`. Contains a Python 3.10+ standard-library, read-only JSONL event checker; strict event allowlist; original response-source semantic labels, attempt and socket generation correlation; five entirely fabricated fixtures; native evidence mapping and an optional Windows runner. It has **no Frida hook, APK patch, network client, session issuance, passwords, tokens or game traffic**.

### Measured results

- **53/53 Python unit tests PASS** in assistant environment; original source/fixture tests and full clean-extraction rerun.
- **ZIP CRC and SHA256 manifest PASS**, 14 file hashes checked.
- Five synthetic scenarios exercised: complete claimed handler sequence, relay HTTP success alone, upstream Stage0 VIP rejection, late VIP rejection after Garden client-load, and stale callback after GameServer reconnect.
- All output summaries have `input_provenance_verified=false`, `official_game_authentication_proven=false`, and `garden_server_authorization_proven=false` even when the fabricated native-marker chain is complete.
- Historical replay of **only sanitized** October 8 JPCG diagnostic JSON found **20 application-positive relay replies (3 Stage0, 17 Stage1)**, all with 12-byte relay session strings and hex-shaped data. **Zero native LoginServer/GameServer/Garden handler branch markers are present in those HTTP logs.** This is a measurement limitation, **not proof the game login did not actually proceed**. Archived real `servertype` JSON types remain unrecoverable from the v3 logger's type-collapse; the `vip_expiry_present` logger field tested PHP integer type, not field existence.

## Important next boundary

The next genuinely new evidence would be a **permissioned**, non-mutating observation of the existing original client processing *actual server-origin* LoginServer, CCU, GameLogin, channel, GardenLogin and GardenData response-handler branches, correlated to the same connection/attempt. That requires known-good provenance/authority and is independent of our future CGM membership gate. The old Nox Frida emulated-realm attach failed **before** observer hooks were installed; this phase intentionally made no new Nox test or incompatible native hooks.

A local event file declaring success is not independently trustworthy, and a positive relay envelope or client Garden-loaded flag is not an authenticated original-server verdict. Real independent CGM upstream compatibility still requires an **authorized** intermediary packet/session processing contract, official account linkage and server acceptance; no VIP denial is overridden or repackaged into a false success.

## Environment and privacy

JPCG `public/`, live Render/Cloudflare endpoints, official servers, original APK, experimental Nox `127.0.0.1:62033`, rollback Nox `127.0.0.1:62030`, and any default GitHub branch were not touched. No credentials, account identifiers, relay tokens, protected query URLs, packet bytes, CCU values, or session keys were emitted or added to the ZIP.