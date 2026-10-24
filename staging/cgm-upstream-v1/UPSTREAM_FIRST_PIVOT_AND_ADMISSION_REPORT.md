# CGM Upstream-First Pivot — 2026-10-23

## User-defined scope (supersedes further Unity probe recommendations)
No more APK modification as immediate plan. User wants a CGM-controlled upstream using existing client HTTP endpoint contract; the CGM DB is the VIP membership authority. Preserve original official game authentication; compare BASE/MOD mobile upstream patterns and new uploaded MoveOnGarden_1007.apk.

## Verified original 1026 contract
Earlier read-only BASE/MOD comparison: identical original IL2CPP client native logic and Android DEX in compared versions, five service URL hosts swapped. MOD upstream source was **not** recovered from the binary. Client's first HTTP config request is /authenticate.php?version=1026 before any credentials; /session.php?data= stage0 carries a SecurePacket-encrypted `packetHex~0~` envelope. Login packet bytes: 2-byte total length, 4-byte counter, command bytes 1,1, length-prefixed INPP.account, length-prefixed password, length-prefixed client version. Existing /verify.php and /authfirst.php query CGM-owned membership; existing public/_cgm_session_bridge.php forwards original request and returns origin's response, default anlgarden.com, without CGM stage0 VIP check or independent successful session processor.

## New uploaded MoveOnGarden_1007.apk inspected read-only
SHA256 3f547d86880c3d2d390af38e627cead81113f8dea44011b847c29685d1f10e8c. Unity IL2CPP, 136 ZIP entries. ARM64 libil2cpp.so 20,308,624 bytes (SHA256 51644f8b0e3e5f427f4b89a27c451a73707e8ef437e3be98dc7b41c112826c7c); global-metadata.dat 3,547,576 bytes (SHA256 3eded09471c4999829bd03611fe38fe2e04e65d7325558b3aceebb1554c028a1). References au.moveongame.com/appapi/gardenapi/envstart.php?appver= and request.php, misc.deathtob.co/api/gardenmobile/set_analytics, LoginServerManager and LoginWebManager. No raw metadata references to authenticate.php, session.php, verify.php, redeemvip.php unlike original CGM/ANLGarden 1026; distinctly different code/assets/version, not domain-only equivalent. No credentials, emulator or network request was used to examine uploaded APK. Need separate mapping if used as technical reference.

## Immediate executable upstream step implemented
Conversation artifact `CGM_Upstream_Admission_v1.zip` SHA256 e36f23b0a578d25ddce8505185cbdd2141f3215484ae0a865deb6f1ff9bbbfc7:
- `cgm_stage0_vip_gate.php`: decrypt original protected relay envelope with caller-provided existing SecurePacket decoder, parse stage0 fixed frame and **only the first account-ID field** (skip password/version without decoding/logging), exact prefixed account lookup via caller-provided existing `cgm_membership_lookup`.
- ACTIVE + unexpired -> `FORWARD_UNCHANGED` (upstream must process unchanged original request and issue genuine game session, never synthesize success).
- INACTIVE/expired -> encrypted *failure* shape compatible with original client `message=VIP`, no session/data. Malformed, unsupported stages, Facebook, DB errors -> fail closed.
- Offline PHP 8.4 lint PASS, 15/15 synthetic tests PASS including checksum-protected packet compatibility and tamper rejection. **No APK or production backend mutated; standalone source not wired to live session.php.**

This is a stage0 *admission gate*, not complete independent session provider. A domain swap + local VIP state does not reproduce missing upstream login/session processor: previous accepted baseline MOD server behavior is not visible from APK client binaries alone. Code MUST NOT log raw query/packet/password/session. Existing origin may still independently deny accounts for BASE VIP; do not overwrite its rejection.

## Next finite implementation step
Integrate admission module into owned staging session gateway behind a disabled-by-default feature flag and a session-processor interface; test synthetic ACTIVE pass-through to a deterministic local stub and INACTIVE VIP rejection, with no official credentials. Then identify an existing supported compatible session processor rather than inventing accepted packet/session data. The build/release gate remains real upstream acceptance under actual account conditions, not just gate unit tests. No more Unity/IL2CPP diagnostics unless they answer a concrete upstream incompatibility.
