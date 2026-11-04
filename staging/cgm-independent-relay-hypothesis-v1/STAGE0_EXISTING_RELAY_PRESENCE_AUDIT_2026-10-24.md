# CGM 1026 — Stage 0 existing-relay presence audit (staging only)

Date: 2026-10-24.
Branch: feat/phase40-staging-provider-interface.
Status: read-only source plus 53/53 synthetic local checks. No production, Nox, APK, or official game-service operations.

## Source-derived compatibility gap
The October 8 archive contains two earlier Stage 0 requests with an existing mobile relay-session field, whereas the fresh 13:49 request had no preexisting relay-session value. The original BASE intermediary returned application-level positive envelopes in all three cases. The existing staging CgmClientPacketInspector rejects *every* Stage 0 request with a nonempty relay field. Its deliberate policy is therefore stricter than the set of historical request structures. The positive envelopes do not prove earlier Garden authentication or validity of stale session IDs.

## New files
- src/Stage0SessionPresenceAudit.php: read-only, already-decrypted plaintext Stage 0 envelope classifier, accepting only original framed 01 01 login requests and three length-prefixed fields with INPP. or INFB. username namespace.
- tests/stage0_session_presence_audit.php: 53/53 PASS locally on PHP 8.4; source and test syntax lint PASS.
- Recognizes STAGE0_NO_RELAY (empty) versus STAGE0_EXISTING_RELAY_UNTRUSTED (nonempty), plus INVALID for malformed input.
- Never prints login IDs, passwords, full frames, relay identifiers or encryption key values. Returns only redacted metadata; all recognition outcomes explicitly set authorized_provider_may_forward=false, relay_session_authenticated=false, official_identity_bound=false, game_authenticated=false.
- All fixtures synthetic; no real username, password, archived URL, token or original protected packet was used in tests.

## Safety and scope
This is a SEPARATE offline audit-only component. The existing strict CgmClientPacketInspector and Stage 0 provider gate are unchanged; recognizing an old relay field MUST NOT automatically make it trusted. The audit component cannot send HTTP, forge game packets or create mobile relay sessions, and it makes no game authentication claim.

## Remaining engineering gate
Original upstream behavior for preexisting Stage 0 relay IDs (re-login, rotation, or ignored stale state) is unverified. A genuine independent integration still needs authorized processor documentation or source establishing exact session ownership and binding; accepted game-bound byte transformation; and legitimate original LoginServer/GameServer authentication. This work does not solve those dependencies.

Test command from an existing repository checkout:
php staging/cgm-independent-relay-hypothesis-v1/tests/stage0_session_presence_audit.php

Production public/session.php, main branch, original APK, Nox environments and official game servers remain unchanged.
