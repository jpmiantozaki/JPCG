# CGM upstream — Public server protocol compatibility (2026-10-23)

**Decision:** No verified, documented, reusable server-side processor found for original ANLGarden/CGM appver 1026 with its `session.php` contract. **No APK, live server, or production changes.**

## Source-grounded comparison

| Evidence | Original CGM / ANLGarden 1026 | Public Dance! Online server | Community Audition Season 5 (2019) emulator |
|---|---|---|---|
| Identity | PlayPark Audition mobile intermediary/client | **Different game**, Dance! Online | Audition Online older Season 5 client family |
| Source | `CGM PACKET MAP.txt` archived Ghidra | `sebastian-heinz/net.arrowgene.dance`, `PACKET.md`, `server/.../packet/Packet.java` | RaGEZONE thread 'Audition Java Source (RedBabana 2019 client)', March 20, 2026 |
| Login frame | Little-endian u16 total length + u32 incrementing counter + command bytes `1,1` + three one-byte-length-prefixed strings (provider-prefix login, password, client version) | u16 length + u16 packet ID (e.g. 1000) + NUL-terminated username + 32-character MD5 password hash + version fields | Published guide identifies Season 5, Java server and 2019-era client; no evidenced ANLGarden 1026 frame |
| Relay HTTP API `session.php?data=` | Known and required by mobile APK | Not documented / not equivalent | Not documented / not evidenced |
| Reply `MGRelayResponse` and continuation token | Required for original APK (`error,message,data,servertype,session,vip_expiry`) | Not present in documented packet framing | Not demonstrated in published guide |
| Production game-service interoperability | Unproven for independent CGM | No claim | No claim for current PlayPark services |
| Fit for independent CGM `session.php` | Required target | **REJECT: demonstrable frame mismatch** | **REJECT AS DROP-IN; reference only until a matching protocol is proved** |

The documented Dance! Online packet serializer uses a 4-byte header and writes [packet length, 16-bit packet ID], with its login packet 1000 fields. The client mapped original 1026 login packet instead has 2+4+1+1 byte framing before strings. Similar game genres and little-endian lengths are insufficient proof of matching protocols.

Community Season 5 emulator published example uses local LoginServer port 18505, GameServer port 18506 and guide-era client support, but ports by themselves do **not** prove or refute protocol compatibility; lack of corresponding mobile `session.php` handler and current PlayPark 1026 proof is the blocker. The RaGEZONE guide also discusses client anti-cheat modification; **no such bypass is performed or recommended in this investigation**.

## Precisely what an acceptable reference must demonstrate

1. **Exact endpoint contract:** `/authenticate.php?version=1026`, `/session.php?data=`, native `SecurePacket` envelope `packetHex~stage~relaySession` and native encrypted `MGRelayResponse` fields.
2. **Two-stage semantics:** stage0 LoginServer path and stage1 GameServer path, original framed packet bytes, relay-session lifetime, and no fabricated `error=false`.
3. **Packet adaptation:** source or documented clean-room implementation that derives *valid* outgoing TCP game packet data from an authorized input while preserving official server authentication; can't infer an algorithm from one 58→~63 byte observation.
4. **Trust:** recognized, legitimately obtained official game response and CGM-member-to-authenticated-game-account binding; a user-supplied login name or relay token alone is insufficient.
5. **Scope:** source code licensing/permission to reuse; no extracted credentials, tokens, private server code, or external login replays.

## Current conclusion

This candidate check **rules out a false shortcut**: copying a superficially similar open-source dance-game server will not make original CGM/ANLGarden Mobile Garden functional with PlayPark. The archived v3.1 40/40 PHP and 11/11 route checks remain meaningful *only* for staging response admission and continuity, not as evidence of an independent valid session issuer.

**Next engineering gate**: require legitimately usable `session.php` server-side source or an independently specified compatible service with sanitized, origin-attested test cases before implementing packet-success generation. Continue CGM-owned membership, safe replay/timeout, and transport robustness work separately if needed; do not forge valid game/session results.

## Links consulted
- Public Dance! Online implementation: https://github.com/sebastian-heinz/net.arrowgene.dance/blob/master/PACKET.md
- Public packet serializer: https://github.com/sebastian-heinz/net.arrowgene.dance/blob/master/server/src/main/java/net/arrowgene/dance/server/packet/Packet.java
- Community 2019 Audition emulator guide: https://forum.ragezone.com/threads/audition-java-source-redbabana-2019-client.1262567/
- Original project: `CGM PACKET MAP.txt` and JPCG staging boundary report.
