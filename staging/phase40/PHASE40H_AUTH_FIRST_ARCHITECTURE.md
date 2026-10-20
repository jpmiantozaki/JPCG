# Phase 40H — Corrected Auth-First boundary and PC response evidence

Prepared 2026-10-20. **Read-only analysis; no game connection or deployment.**

## Answer to the identity-handoff question

JPCG does not need an independently verifiable PlayPark identity assertion
to decide whether the *claimed* login identifier is eligible to attempt
the **original** PlayPark login. That is a pre-auth membership decision,
not account authentication. The original PlayPark LoginServer must still
validate real credentials, and the official GameServer and GardenServer
must still accept legitimate session state. CGM eligibility alone
does not establish that the claimant owns that login identifier.

Previous Phase 40 staging gates model a stronger optional server-side
identity-binding requirement. Those tests passing does not mean the
identity assertion exists or must be added before a precheck can work.

## Evidence sources checked

- Current `public/authfirst.php` returns `eligible_to_attempt` and
  `game_authorized=false`. Its strict-W format returns membership status
  and TTL, not session credentials.
- Current `public/verify.php` decodes client-provided identity using a
  checksum-bearing protected packet. The checksum is not proof of official
  account ownership.
- Current `public/authenticate.php` and `public/session.php` still call
  `_cgm_session_bridge.php`, which forwards to the configured upstream
  (default `https://anlgarden.com`).
- Prior PC static audit Phase 3.3W mapped inbound message ID `0x01` to
  official executable handler `0x00A1F6D0` (vtable pointer `0x00FD71CC`).
- New read-only GNU objdump inspection of the same official PE32 executable
  (SHA256 `a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911`)
  confirms that this handler reads two leading bytes from its packet-reader
  at `0x00A1F6EC` and `0x00A1F6F8`, branches on them at `0x00A1F71D`
  and `0x00A1F725`, and, for the zero/zero branch, reads a
  variable-length string into a client object buffer at offset `+0x1778`
  using the region `0x00A1F744`–`0x00A1F779`.
- Other leading-byte values enter distinct branches at `0x00A1FF2E`
  and `0x00A20372`. The semantic meaning of the bytes and the copied
  string is **not** established. Do not label either the string or these
  branches a signed login token or valid session.

## Important separation

| Layer | CGM controls | PlayPark controls |
|---|---|---|
| Membership eligibility before login | ACTIVE/expiry decisions for a claimed ID | Not required for this precheck |
| Original credentials | No storage or verification needed for membership | Credential verification |
| LoginServer/GameServer exchange | Transport only if permitted and protocol-compatible | Authentication/session issuance |
| Garden authorization | No artificial CCU or userSN values | Genuine session acceptance |

## The remaining implementation blocker

Mobile `RelayWebManager` asks a third-party HTTP upstream to return
`MGRelayResponse.data`, which the APK transmits to LoginServer (server type 0)
or GameServer (server type 1). The real upstream contributes more than
VIP membership decisions. JPCG's current bridge still delegates this
operation rather than performing a proven independent, authorized exchange.

**No PlayPark-issued, independently verifiable identity assertion or
officially authorized replacement session source has been identified.**
Reverse engineering client response parsing cannot establish the
issuer's private server state or validate permission to operate a gateway.

## Practical Phase 40I priority (no placeholder tests)

1. Treat `authfirst.php` as the already-implemented membership precheck.
   On INACTIVE, EXPIRED, ERROR or timeout, the CGM client must not
   invoke original credential submission. When ACTIVE, allow the
   *original* PlayPark login unmodified. Recheck the exact login ID
   immediately before dispatch to prevent account switching.
2. Keep the membership gate distinct from the upstream GameServer
   relay. Test the actual original APK under a permitted test setup
   for membership-gating and original-login continuity; a UI popup
   or CGM ACTIVE message is not proof of Garden entry.
3. Separately establish a legitimate, approved source of session
   exchange data if BASE/MOD dependence is to be removed. Do not
   synthesize GameServer responses, CCU keys, or Garden login success.
4. Preserve experimental Nox `127.0.0.1:62031`; never touch rollback
   `127.0.0.1:62030`. Do not modify the official PC EXE, production
   JPCG routes, Render, or Cloudflare as part of this checkpoint.

## Exit criteria

- A real CGM APK enforces ACTIVE at credential submission (including
  alternate inner/outer Login button paths), preserving original credentials.
- Official game login and Garden session continue to work under an
  operator-permitted test configuration without BASE/MOD relay dependence.
- Backend logs reveal only non-sensitive statuses. No password, packet
  payload, bearer token, or protected query strings are collected.

**Current status:** Phase 40C–G offline tests passed. The release and
independent session-source criteria above are still unverified.
