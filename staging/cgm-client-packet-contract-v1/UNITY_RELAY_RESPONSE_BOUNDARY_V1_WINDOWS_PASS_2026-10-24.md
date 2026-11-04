# CGM Unity Relay Response Boundary v1 — Windows confirmation

**Date:** 2026-10-24

## User-confirmed results

The user ran the local Windows PHP suite from `CGM_Unity_Relay_Response_Boundary_v1.zip` and provided the full test output: **ALL 75/75 PASS**. All test names reported PASS, including:

- `positive-is-unverified-shape`, `positive-is-not-game-auth`, `positive-is-not-cgm-membership`;
- `upstream-VIP-rejection-preserved`, `rejection-is-separate-kind`;
- `response-login-target-is-zero`, `stage1-server-type1-matches`, `mismatch-not-rewritten`;
- `opaque-reply-preserved-byte-identical`, `client-request-preserved-byte-identical`;
- `response-metadata-does-not-leak-secret-session`, `json-log-excludes-account`, `json-log-excludes-password`, `json-log-excludes-relay-token`;
- `unmapped-stage1-failclosed`, `invalid-provider-response-failclosed`, `provider-outage-failclosed`, `decoder-outage-failclosed`;
- `no-auth-issuer-method-exposed`, `no-vip-grant-method-exposed`.

This builds on the user's earlier independent Windows **34/34 PASS** on the read-only Unity client packet request inspector, and **38/38 PASS** on the experimental identity boundary. These are separate suites; do not misstate them as live protocol acceptance. The reported result validates the local synthetic/negative-path behavior of the staging code, **not a working independent `session.php`**, legitimate PlayPark authentication, live packet acceptance, or Garden access.

## Next integration boundary

Keep original BASE/MOD client routes and original decrypted packet and protected HTTP response formats unchanged. The staged boundary accepts only three mapped request types: stage0 `01 01`, stage1 `00 16` and `01 00`; additional legitimate Stage1 commands may exist. It does not issue authenticated responses, bypass mobile VIP, or mint real relay/game sessions. Only connect it to an actually authorized, game-compatible upstream provider after establishing genuine authentication, relay-session binding, endpoint compatibility, and privacy controls. Any mismatch between request stage and response `servertype` should be observed separately; native client callback uses response `servertype` to route packets and does not enforce equality.

**No action on APK, rollback Nox, production JPCG or original game servers.** Keep the ZIP as source backup; existing GitHub checkpoint is documentation, not complete source.
