# CGM — BASE/MOD session.php comparison and upstream processor decision
**Date:** 2026-10-23. **Scope:** archival/static evidence comparison only. No Android/Unity APK changes, no live network access, no credentials, tokens, game packets or requests replayed.

## Executive conclusion

The immediate backend problem is narrower than **replacing game authentication**: in the mapped APK, the mobile `session.php` response carries a packet `data` that is dispatched **back into the client's existing connected original login/game socket** by `RelayWebManager.<RelayServerData>b__11_0` (`Session.Send` for type 0, `GameServerManager.SendPacket` for type 1). Game-server authentication remains with game services. The role of BASE/MOD mobile upstream plausibly includes membership admission, adaptation/preparation of packet bytes, and relay-session tracking. The APK does not establish how MOD server-side code transforms its requests or obtains relay session state. **Do not assert that MOD only looks up VIP.**

## Exact BASE/MOD client parity

Preserved user-verified 2026-10-19 original BASE vs MOD comparison:
- ARM64 `libil2cpp.so`: identical SHA256 `FB569843B5955B0BBBEBC143D5421942160D4DFE6F70CB7853E85CACE8B02FC8`, each 27,093,976 bytes.
- Android `classes.dex`: identical SHA256 `1719E3F09AE56A531116634967FD7106DE384E3D282FA212174ED5CF46844140`.
- `global-metadata.dat` size 4,906,768 each, with **60 changed bytes** over ten contiguous regions, all five same-length mobile-service hostname substitutions `anlgarden.com` to `m.cabahug.xyz` (analytics, authenticate, redeemvip, session, verify). These are original BASE/MOD values, not CGM's separate `wtn.pages.dev` comparison.
- Same compiled client handler therefore processes both endpoint responses; **server implementations, membership databases and sessions can differ** without different ARM64 login code. Other Unity assets differ in some compared builds and cannot be assumed equivalent.

## Existing historical results — do NOT conflate origins

| Field | Successful BASE-eligible test | MOD-member test routed to BASE |
|---|---|---|
| HTTP origin actually observed | BASE / anlgarden.com via CGM bridge | BASE / anlgarden.com via CGM bridge |
| First configuration | `/authenticate.php?version=1026` HTTP200 | route generally present; not proved distinct successful MOD config |
| `/session.php` request stage | 0 | 0 |
| Original stage-0 client packet bytes | 58 | 57 |
| Initial relay session | absent | absent |
| Logical response | `error=false` | `error=true,message=vip` |
| Response data | present, around 63 decoded packet bytes | absent |
| Relay session | present, 12 bytes | absent |
| Stage-1 continuation | observed | not reached |
| Subsequent CGM membership check | ACTIVE observed | not reached |
| Garden | reportedly entered | no VIP screen |

This historical table captures two attempts **both at BASE upstream**, with different accounts and membership conditions. It is **NOT** a BASE-success versus MOD-success comparison, and the packet length difference (58 vs 57) cannot be attributed to BASE vs MOD backend behavior.

No complete **successful `m.cabahug.xyz` MOD-origin stage0/stage1 trace** was located among the available indexed source reports. User history states MOD VIP works with MOD APK, but the archived record does not include the matching sanitized response/session metadata needed for a direct server-behavior comparison.

## Mapped single binary network flow

1. `/authenticate.php` returns encrypted `MGServerResponse` with official relay/login/game IPs, ports and versions. Successful config is **not** a password authentication outcome.
2. `LoginServerManager.ConnectToLoginServer` invokes original `Session.Connect` to configured game login service.
3. User's normal CONFIRM invokes `Login()` -> `SendLogin()`. Client serializes 2-byte size + 4-byte counter + command [1,1] + length-prefixed `INPP.` login, password and client version.
4. `RelayWebManager.SendRelayInfo` forms `packetHex~0~relaySession`; `RelayServerData` protects it and requests mobile `/session.php?data=...`.
5. Callback decrypts and parses `MGRelayResponse(error,message,data,servertype,session,vip_expiry)`.
6. If error==false, client stores returned **intermediary relay session**, decodes response data, and dispatches to `Session.Send` (server type0 login) or `GameServerManager.SendPacket` (type1 game). This is an existing game socket path; upstream HTTP SUCCESS alone is **not** official game login acceptance.
7. Existing socket response callbacks then handle authentic original login and game state, followed by CCU/game/Garden paths. Some response parsing/transformations remain unmapped.
8. Later `verify.php` and `redeemvip.php` mobile membership operations are separate from the official game password check.

## Positive evidence and engineering implications

- BASE stage0 request: 58 original packet bytes; accepted BASE response: ~63 output packet bytes. **A 5-byte difference in this single historical observation** establishes at least that those two byte arrays differ in length. It does *not* identify why: packet envelope/headers/transformation and parsing conventions remain possibilities. Do not claim this is a constant 5-byte prefix or synthesize a modified packet.
- The base response also allocates/returns a 12-byte relay-session value, later supplied by the client in stage1. It is mobile intermediary session state and must not be assumed an official game token.
- Therefore **CGM v3's VIP check + HMAC continuity are necessary policy components but insufficient** for a standalone MOD-equivalent upstream. v3 currently forwards all credential-bearing upstream requests to an explicitly configured existing origin rather than supplying a genuine accepted packet. It cannot remove an upstream's independent VIP rejection.

## What can be concluded about MOD

**Proven:** BASE and MOD client binaries call corresponding HTTP routes and share compiled packet/login code. Original client connects to configured game service and sends the HTTP service's accepted `data` to that existing socket. MOD server may implement compatible packet-preparation/reply/state logic in a different backend.

**Not proven:** MOD's PHP source, successful MOD-origin response shape/size/stage sequence, packet transformation algorithm, session lifecycle, whether MOD performs anything other than VIP+relay adaptation, or whether JPCG v3 could be independent by only replacing membership lookup.

## Stop wasting effort — single evidence gap and decision

**Highest-information missing evidence:** A previously permitted, sanitized **successful MOD-origin** `/session.php` transcript with only: origin marker, request stage, incoming packet length, upstream logical error, returned data packet length/hex-valid flag, `servertype`, relay-session present/length (never contents), stage1 count/status, and client final login/Garden success/denial. Capture at most one positive path if the user has legitimate permitted access; don't use interception or intrusion into somebody else's server, don't collect/store/replay real passwords/packet bodies/session values. Use existing archives first. A table without a successful MOD origin cannot distinguish server roles.

**Parallel non-invasive work:** inspect already-owned offline Unity/Ghidra dumps solely for `Session.Send` and socket callback handoff; focus on *which bytes the game server gets* and *where official replies enter the client*, not another generalized IL2CPP probe. No APK build required. PC client's `send`/`recv` mapping may be ancillary and not proof of MOD PHP logic.

**Decision gate:** If a legitimate supported processor can yield original-compatible packet data and session continuity independently of BASE/MOD VIP, connect it to CGM admission. If not, don't simulate `error=false` or rewrite a remote upstream's VIP denial; preserve fail-closed behavior. Source of valid server packet data remains the limiting requirement.

## Source index
- Project Library: `CGM_BASE_MOD_Investigation_Chat_Backup_2026-10-19.txt` (original BASE/MOD hashes), `CGM PACKET MAP.txt` (Ghidra relay+socket calls), `CGM-Conversation-Backup-2026-10-08.txt` (sanitized historical BASE trace and failed-to-BASE MOD-member trace), `CGM-Session-Flow-Findings.txt` (original-client send/response semantics).
- JPCG `public/_cgm_session_bridge.php` default origin BASE and preserves response; `public/authfirst.php`/ `public/verify.php` separate CGM membership.
- No new native disassembly run or source outside retained evidence was claimed.
