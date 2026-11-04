# CGM / ANLGarden 1026 — original client packet-processing boundary
**Evidence-driven static checkpoint | 2026-10-23 | No APK rebuild, Nox use, live traffic or production route changes**

## Decision in one sentence

The existing original 1026 client **already owns the LoginServer and GameServer socket connections**. Its normal `Session.Send` path (`sendEncryptionType=0`) transmits byte arrays returned in `MGRelayResponse.data` **without an additional client-side outgoing encryption transformation**. An independent CGM mobile upstream must therefore deliver genuinely compatible packet bytes and relay-session state to the client **before** that socket-send point; CGM VIP membership alone cannot generate those bytes or prove the game accepted them.

## What the archived Ghidra analysis actually established

| Boundary | RVA in ARM64 IL2CPP | Observed behavior | Implementation owner |
|---|---|---|---|
| Build login | `LoginServerManager.SendLogin`, 0x6A80C0 | Builds the original framed login packet (size/counter/command [1,1]/three variable strings) | APK |
| Protected HTTP envelope | `RelayWebManager.SendRelayInfo`, 0x6A82AC; `RelayServerData`, 0x6B04CC | `packetHex~stage~relaySession`; applies `SecurePacket`, invokes `/session.php?data=` | APK + mobile intermediary |
| Client receives HTTP answer | `<RelayServerData>b__11_0`, 0x6B088C | Decrypt SecurePacket, parse `MGRelayResponse(error,message,data,servertype,session,vip_expiry)`, store relay session for success; `error=true,message=VIP` enters VIP error UI | APK |
| Dispatch successful data | Same callback 0x6B088C | Decode hex data, `servertype=0` to login `Session.Send`, `servertype=1` to GameServerManager.SendPacket | APK |
| Game forwarding wrapper | `GameServerManager.SendPacket`, 0x69BF08 | Forwards supplied byte[] to an already existing Session if nonnull; not an auth/session issuer | APK |
| TCP send | `Session.Send`, 0x6BE850 | When session connected and sendEncryptionType=0, sends caller-supplied packet bytes *without additional outgoing cryptography* | APK |
| TCP receive | `Session.OnReceive`, 0x6BE528 | Accumulates fragmented frames, reads declared packet lengths, decrypts/normalizes according to receive mode and invokes OnPacketReceived | APK |
| Observed receive mode | `recvEncryptionType=2` | `PacketEncryption.Decrypt` on incoming socket data; **different layer from HTTP SecurePacket**; exact algorithm not fully mapped in archived analysis | APK |
| Official login response | `LoginServerManager.OnPacketReceived`, 0x6A738C | Reads server's transformed frame, handles command [1,1] and status, populates connection/shared state; success path toward game connection | Game service response consumed by APK |
| Game continuation | `SendVerifyCCU` 0x69A504, `SendLogon` 0x69B9D8 | Original client creates additional 16-byte game/CCU request frames, relays as stage1 | APK + mobile intermediary |

Client mode fields were inspected previously at offsets 0x40/0x44, initialized from constant 0x127B2D0 as `sendEncryptionType=0`, `recvEncryptionType=2` for both LoginServer and GameServer paths. These facts come from the archived `CGM PACKET MAP.txt`, not a new Ghidra run. Ghidra VA in these user's prior reports is ELF RVA + 0x00100000.

## Three different transformations — keep separate

1. **HTTP protection**: `SecurePacket` obfuscates/protects the `session.php` envelope and encrypted JSON response. JPCG `public/_secure_packet.php` implements this framing and checksum.
2. **Mobile intermediary packet preparation**: the *remote server-side* logic that supplies `MGRelayResponse.data` and `.session`. **UNKNOWN** for MOD source and not contained in the inspected APK. It might modify packet framing, counter, relay metadata or other bytes; no specific algorithm is established by the client code alone.
3. **Original game socket transport**: the APP's `Session.Send` mode0 passes the packet as bytes to an established TCP socket; `Session.OnReceive` mode2 performs separate *incoming* PacketEncryption decoding. No additional client-side outgoing transform fills the missing mobile intermediary function.

Do **not** misinterpret a known SecurePacket decoder or client socket-mode proof as a server-side packet preparation algorithm.

## What historical lengths do and do not prove

Archived 2026-10-08 BASE-origin eligible test: stage0 request 58 decoded bytes, successful HTTP answer with ~63 decoded `data` bytes and a 12-byte relay session, later stage1. This suggests the successful output was a different size from the input. It does **not** establish where or why bytes were added (let alone a reusable +5-byte rule).

Later four sanitized source-91fcda21919f records contain stage0 output 67 bytes and stage1 outputs 22/25/17 bytes, each 12-byte relay session, but **no origin, HTTP status, servertype or hex-validity fields**. They cannot be attributed to MOD or even to a single shared live session; they are not safe proof of a MOD packet transformation.

## Current JPCG implementation — checked source, not assumed deployment

- `public/session.php` in branch `feat/phase40-staging-provider-interface` calls `cgm_session_bridge('session.php')`.
- `public/_cgm_session_bridge.php` constructs original-origin URL from `CGM_ORIGINAL_ORIGIN` (default BASE `https://anlgarden.com`), forwards GET query/POST body and preserves real upstream HTTP status, response headers and body.
- `public/_cgm_session_diagnostic.php` can derive stage/request byte lengths and an upstream host label for structured logs, but **does not implement a relay packet adapter or issue sessions**.
- Existing v2/v3 staging bundles implement CGM membership admission and keyed local follow-on relay continuity; user confirmed local 32/32 and 37/37 tests. They are **not** automatically installed in `public/session.php` and their positive cases depend on a mock or existing compatible upstream rather than a genuine independent MOD-equivalent packet processor.
- Consequently if the configured existing origin returns `error=true,message=VIP`, JPCG cannot turn that rejection into a valid official login by substituting `error=false`; the client needs valid packet bytes and original game-service acceptance.

## Implementation boundary and finite stop/go gates

**Already implementable and verified locally:** CGM DB VIP admission for supported account identifier, reject inactive/expired, preserve original authenticated-provider request payload unchanged, parse and preserve existing origin's protected response, stage continuity if backed by genuine session, fail closed.

**Cannot faithfully implement from current evidence:** the intermediary's successful packet-preparation algorithm, protocol-correct fresh relay session generation, real provider response and identity binding, MOD server PHP source and internal behavior. None can be recovered merely by examining identical BASE/MOD client code or creating more fake fixtures.

**One real next engineering input:** a **permitted source-attributed compatible-session processor implementation** (e.g., owned server source or a documented legitimate game-service integration that produces actual packet bytes and session state), and non-sensitive protocol test vectors generated specifically for that processor. At minimum it must establish:
- Which valid input type/stage is accepted and what the correct output byte framing is, without exposing real credentials.
- Whether relay-session state is created and how stage1 is bound to it.
- How the returned data is verified by **original game server response**, not just by HTTP `error=false`.
- How CGM ACTIVE is bound to a legitimately authenticated account ID without treating client-supplied `INPP.` as proof of ownership.

**Stop:** no v4 fake-success endpoint, no repeated broad Ghidra/Unity/Nox phase, no repurposing recorded session tokens, and no production deployment. Synthetic tests are not independent Garden login acceptance.

## Additional design nuance

The client's original socket `Session.OnReceive` is a **downstream parser**, not an API the PHP mobile intermediary calls. Mapping its PacketEncryption algorithm can clarify authentic game-response decoding for offline research, but cannot itself disclose the MOD upstream's server-side source. The original client uses separate official and intermediary sessions: `MGRelayResponse.session` is relay continuity, whereas official game shared-state fields (which feed CCU/logon) come from original game-server responses. Keep these credentials/states distinct.

## Source index

- Archived Library `CGM PACKET MAP.txt`: Ghidra 8 Oct 2026 packet, socket transport and receive-mode disassembly.
- Archived Library `CGM_AuthFirst_Complete_Backup_2026-10-15.txt`: relay callback and `GameServerManager.SendPacket` verification.
- Archived Library `CGM_BASE_MOD_Investigation_Chat_Backup_2026-10-19.txt`: original BASE/MOD native equality and endpoint swaps.
- Archived Library `CGM-Conversation-Backup-2026-10-08.txt`: sanitized successful BASE and rejected BASE-origin attempt.
- Existing staging docs `BASE_MOD_SESSION_BEHAVIOR_COMPARISON_2026-10-23.md`, `MOD_ORIGIN_ARCHIVE_AUDIT_v1.md`.
- GitHub source `public/session.php`, `public/_cgm_session_bridge.php`, `public/_cgm_session_diagnostic.php`, `public/_secure_packet.php` on this staging branch.
