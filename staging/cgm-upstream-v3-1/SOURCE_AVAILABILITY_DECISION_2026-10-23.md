# CGM backend source availability decision — 2026-10-23

No production services, APKs or live sessions were accessed during this inventory.

## Verified JPCG source paths

- `Dockerfile` copies only `public/` into the PHP/Apache image.
- Root `session.php` calls root `_bridge.php`, which forwards to a historical MOD hostname; the root copies are not included by the current Dockerfile.
- `public/session.php` calls `public/_cgm_session_bridge.php`, which forwards to the configured origin (default BASE). This source does not independently prepare accepted packets.
- Staged v3.1 PHP has CGM membership admission, session-fingerprint continuity, and a synthetic-tested stage/type check, but no independent session issuer or official game authentication capability. The local 40/40 and 11/11 tests measure the forwarding/admission policy, not game login success.

## Public source check

Public web/GitHub code searches for ANLGarden, MGRelayResponse, MoveOnGarden and the matching PHP relay interface did not uncover a directly usable, verifiable, public original 1026 mobile-session processor. This absence of search results is not proof that no source exists. Unrelated game emulator projects are not protocol-compatible evidence.

## Decision

Do not infer an independent MOD-compatible backend from a hostname change, the root legacy PHP proxy, a protected-packet parser, or the passing v3.1 synthetic tests. A real compatible packet processor/source or a documented supported session integration remains the missing dependency.

Keep deployed gateway unchanged. Avoid fake success responses, credentials or session-token copying, and avoid repeating synthetic suites without newly supplied protocol evidence.
