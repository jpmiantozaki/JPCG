# CGM Unity Stage 0 membership preflight → authorized provider: offline checkpoint (2026-10-24)

## New implementation (conversation artifact, not deployed)

**Archive:** `CGM_Unity_Stage0_Provider_Gate_v1.zip`

**Archive SHA256:** `8133655fe1b282381be4dda297d9ef9619401944772a198b5107c88a6b607887`

**New PHP source SHA256:** `0e7a71586004503a9efa6cc00e6c6e31a872b06534cee871d1d46286a5a8c29a`

The archive is the **runnable source of truth** (PHP sources, README, new tests, test output and manifest). This checkpoint is a technical record, **not** a replacement for the archive and not the full code. All code in the ZIP is offline/staging-only.

## Actual work completed

Inspected current `public/session.php`, `public/_cgm_session_bridge.php`, `public/authfirst.php`, `public/verify.php`, `public/_cgm_membership.php`, `public/_secure_packet.php`, `public/redeemvip.php`, and `public/_authfirst_resolve.php`.

The new `src/Stage0ProviderGate.php` composes the existing **unchanged** Unity request inspector and response boundary:

1. Decrypt the **original** Stage 0 protected request through an **injected** original SecurePacket decoder and validate exact serialized Unity login frame `01 01`.
2. Privately read the exact case-preserved `INPP.` or `INFB.` **claimed login** (never expose it or the password in metadata).
3. Invoke an injected backend membership lookup. Require `scope=CGM_MEMBERSHIP_ONLY`, `state=ACTIVE`, and integer `vip_expiry > trusted server time`.
4. **Only then** invoke the explicitly authorized provider callback with the **original unmodified protected request**; the existing response boundary preserves the entire original protected provider response byte-for-byte. Any upstream `error=true,message=VIP` remains rejection. A positive *shape* is not recognized as real game authentication.
5. Stage 1 and unmapped packets are **not enabled**: no trusted authenticated identity, official LoginServer acknowledgment, relay-session continuity, or provider packet-issuer interface has been established.

No automatic stripped/unstripped login prefix equivalence. `_authfirst_resolve.php` documents historical DB IDs in both plain and `INPP.` forms. That is a **real compatibility uncertainty**, not evidence that those IDs are equivalent for entitlement. `redeemvip.php` stores the user-submitted ID verbatim; verify the exact relationship before any rollout.

## Test outcomes

- New local `tests/run.php`: **ALL 66/66 PASS**, synthetic `FIXTURE|` protected packet only. 
- Unchanged prior Relay Response Boundary v1 regression: **ALL 75/75 PASS** locally.
- Unchanged prior Client Packet Contract v1 regression: **ALL 34/34 PASS** locally.
- All PHP files syntax PASS and ZIP integrity PASS.
- The **66 new suite has not yet been run by the user on Windows**. Earlier 75/75 and 34/34 were independently confirmed by the user in Windows.

## Windows test (only new suite)

Extract ZIP into `D:\CGMTEST`:

```powershell
& 'D:\CGMTEST\PHP\php.exe' 'D:\CGMTEST\CGM_Unity_Stage0_Provider_Gate_v1\tests\run.php'
```

Expect `ALL 66/66 PASS`.

## Critical blockers — no live route

- **No independent compatible authorized `session.php` processor identified.** Live JPCG `public/session.php` still delegates to configured BASE/MOD origin, default BASE; CGM ACTIVE alone does not make upstream VIP errors disappear and does not produce accepted LoginServer/GameServer packets.
- **No authoritative pre-auth identity:** a typed or serialized login ID is a claim only. CGM ACTIVE preflight can deny an ineligible attempt, not prove PlayPark ownership or authorize game access.
- **No legitimate Stage 1 session proof:** user-supplied HTTP relay continuity token is not official userSN/CCU or authenticated LoginServer evidence.
- **No original client denial response invented:** preflight refusal returns a server-side fail-closed decision only. A client-compatible negative response requires separate design/evidence; never synthesize `error=false`.
- **Sensitive data:** original query carries reversibly protected game credentials. Existing Apache/proxy request logs, HTTP header forwarding and transport must be privacy reviewed before any live integration.

**No Nox, APK, PlayPark/BASE/MOD live authentication, JPCG production public route, or backend settings modified.** Next progress needs authorized provider/session issuer plus verified identity/continuity contract; no amount of structural green tests substitutes for those.
