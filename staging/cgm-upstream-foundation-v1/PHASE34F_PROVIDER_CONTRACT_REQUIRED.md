# CGM Phase 3.4F — External authorization contract (required before live integration)

**Status: NOT PROVIDED / NO GO for independent CGM mobile sessions.**
This is a requirements document, not a claim that PlayPark or the original mobile intermediary offers a public API.

We have finished offline coordination of Stage 0/Stage 1, local native-handler observations, and the existing provider/membership policy. The implementation is committed as an exact hash-checked source ZIP at `staging/cgm-upstream-foundation-v1/source-patches/CGM_Phase34E_Core_CI_Staging.zip`, extracted by CI. The larger conversation artifact contains the 442-case comprehensive suite.

Before the chain can operate as a real product, a permitted provider/operator must supply:

1. **Explicit integration permission** covering the relevant game and Garden services and any original mobile intermediary entitlement.
2. **Server-side trusted identity**: issuer, stable account subject, verified account identifier/email, expected audience, signed or authenticated proof, attested-at and expires-at. A client-provided email/ACTIVE string is not identity proof.
3. **Exact account link**: verified subject to one pre-provisioned CGM membership key; do not infer aliases like `INPP.` automatically.
4. **Genuine provider session processing**: a documented method to verify game/Garden rights and preserve real official session acceptance, including independent intermediary entitlement. Native PCAP response bytes, XOR codecs and fake success JSON are not session authority.
5. **Attempt binding**: provider-issued proof bound to the one-use CGM attempt, correct session and socket generations; reject stale, replayed, expired, mismatched and cross-account evidence.
6. **Supported original-client integration** for pre-login membership gating, exact-once original credential submission and native result handling; the current PHP coordinator is neither installed in the APK nor connected to its network sockets.
7. **Privacy protections**: no official passwords in CGM membership queries; ingress, Apache, CDN, APM and upstream logs must not retain reversible protected credential-bearing `?data=` URLs. Review forwarded header policy before any real-account staging.

**Acceptance proof** must originate from the permissioned official/provider path, not a historical PC/Nox recording or local native-marker list. Verification must bind LoginServer, GameServer and GardenServer acceptance to the same account and login attempt.

**Do not** bypass the third-party VIP rejection, invent a provider-issued session, replay captured token material, set `error=false` on a denial, or deploy experimental `session.php` processing to production.

Current safe behavior: `UnconfiguredProviderVerifier` denies, `session_issued=false`, original signed APK/Nox/PlayPark/production JPCG unchanged.
