# CGM Unity Relay Response Boundary v1 — after 34/34 Windows PASS (2026-10-24)

## New standalone source artifact

Conversation attachment **`CGM_Unity_Relay_Response_Boundary_v1.zip`**, SHA256 `bdfe233d022eb205f3444309b0b937ea75e28636b2f66a27fa278d0751142899`. The full PHP source, tests, README, report and file manifest are in this ZIP. **Not deployed or automatically installed by GitHub.**

- `src/ClientPacketInspector.php` — byte-identical original v1 request inspector (prior user's Windows result: **34/34 PASS**).
- `src/RelayResponseInspector.php` — bounded, redacted validator for original protected `MGRelayResponse` object; parses `error,message,data,servertype,session,vip_expiry` but never returns account strings, token bytes, passwords, actual packets, or a true game-authentication decision.
- `src/RelayExchangeBoundary.php` — staging-only provider interface: checks one of three known original request commands, injects an **explicitly approved** provider function, preserves original protected response byte-for-byte, and separates secret wire response from safe JSON-serializable metadata. Rejection `error=true` / `VIP` is never converted to success.
- `tests/run.php` — **75/75 PASS offline** on simulated requests/responses, malformed replies, provider failures, response stage/type mismatch observation, ciphertext preservation and privacy. The unchanged v1 request suite independently still **34/34 PASS** in the assistant's local runtime. **The new v1 response suite is not yet user-run on Windows.**

## Source-derived findings and decisions

Original CGM/BASE/MOD ARM64 client `libil2cpp.so` SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`. Native `RelayWebManager.<RelayServerData>b__11_0` at `0x6B088C` decrypts a response (`0x6B093C`), parses `MGRelayResponse` (`0x6B0960`), chooses error branch via `error` (`0x6B0970`), and sends positive `data` to LoginServer for `servertype=0` or GameServer for `servertype=1` (`0x6B0B5C` onwards).

The original callback **does not test response `servertype` == previous request stage**. New boundary annotates any mismatch as `MISMATCH_UNPROVEN_POLICY` instead of silently rewriting upstream response. It does **not** establish that successful cross-stage replies exist. v3.1 strict equality guard remains staging-only and unvalidated from origin-attributed success.

The response inspector labels `error=false` responses `UPSTREAM_POSITIVE_SHAPE_UNVERIFIED`; a positive *shape* is not proof of real LoginServer/GameServer acceptance. Ordinary 2-byte frame length annotation is **advisory only**, not enforced. The synthetic fixture encryption is **not** actual SecurePacket; actual SecurePacket decoding is a separate injected dependency.

**NO-GO for live wiring:** source only covers stage0 login `01 01` and stage1 CCU verify `00 16` plus game logon `01 00`; other legitimate Stage1 packets likely exist. A caller must also ensure CGM exact membership admission, genuine identity attestation/official authentication, origin-validated permitted processor, private secret handling, and authorized relay-state continuity. No automatic forwarding to third-party hosts, no game session issuance, no login bypass, no APK/Nox/production changes.

### Run on Windows

Extract ZIP under `D:\CGMTEST\CGM_Unity_Relay_Response_Boundary_v1` and run:

```powershell
& 'D:\CGMTEST\PHP\php.exe' 'D:\CGMTEST\CGM_Unity_Relay_Response_Boundary_v1\tests\run.php'
```

Expected `ALL 75/75 PASS`. Only new suite needed. **Do not delete the source ZIP before archiving it**; this GitHub note is a checkpoint, not a complete source copy.

### Next meaningful work

A genuinely authorized compatible session processor or supported provider interface is still required to turn actual CGM-eligible users' original login packets into game-accepted responses and genuine relay sessions. Improve this staged contract only when a real provider-compatible response specification and permission exist; do not call syntactic success a working login.
