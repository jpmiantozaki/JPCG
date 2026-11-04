# CGM 1026 — authorized session processor discovery: operator and official permission routes

**Research date:** 2026-10-25
**Scope:** public-source discovery of a licensed, compatible mobile Garden integration for CGM. No APK modification, network login probes, proprietary packet captures, credential use, or production changes.

## Executive result

**No publicly verified, drop-in independent 1026 `session.php` processor or publicly documented PlayPark third-party game-login/Garden API identified in targeted search.** This is a search limitation, not proof that such a capability cannot exist privately.

**New actionable lead:** original intermediary operator's public website and related community communications, plus PlayPark's own support/permission process. Prior work already establishes that source-only synthetic packet checks and the JPCG relay bridge are insufficient for actual game acceptance.

## Evidence and direct public references

1. **Existing mobile intermediary:** https://anlgarden.com/ — "Audition Mobile Garden" advertises Android access, subscription pricing, and download, with a direct Facebook social link `https://facebook.com/audition.mobilegarden`. Public page alone offers no `session.php` specification, open-source processor, or third-party developer contract. Source: public homepage retrieved October 25, 2026; indexed page may reflect an earlier crawl.
2. **Mobile Garden community messaging:** https://t.me/s/AuditionNextLevel/3782 — a 2025 community-channel post [AUDITION MOBILE GARDEN] says Mobile Garden is **not officially endorsed**, describes it as a community convenience app, and asserts that its users have not been banned merely for using it. **This is a self-description/claim from that channel, not PlayPark authorization or a server-operator guarantee.** Channel also links to the anlgarden.com site.
3. **Official publisher policy:** https://auditionnextgen.playpark.com/game-policy/ — PlayPark/Audition Next Gen warns against unapproved modified clients/third-party tools and documents sanctions. The publisher's current published rules are distinct from the community app's safety claim. Specific compatibility/license permissions cannot be inferred from either public page.
4. **Publisher support channel:** https://member.playpark.net/ibox2/ib_GenericPage.aspx — PlayPark iBox public support/enquiry form. The official game-policy page also refers users to ESupport. iBox is a support entry point, **not** proof of a publicly available partner API.
5. **Official PlayPark account guidance:** https://auditionnextgen.playpark.com/how-to-register/ — documents registration and sign-in for PlayPark accounts; it does **not** establish an externally issued game or Garden session API.

Targeted searches included public source references for `ANLGarden`, `DeathToB`, `session.php`, `MGRelayResponse`, `servertype`, `vip_expiry`, and PlayPark partner API. Results did not establish a compatible publicly licensed server implementation. Avoid substituting unrelated Audition emulator protocols or mobile games using similar branding.

## Decision tree

### Route A — operator-supported ANLGarden integration (highest near-term compatibility potential, permission unverified)

Ask the owner/operator linked by anlgarden.com whether they:
- Support **documented, consented** third-party mobile Garden clients and relay processors for version 1026.
- Offer an authorized server-to-server integration, licensing option, test environment, or API instead of demanding proprietary source.
- Can explain legitimate **empty/nonempty Stage 0 existing-relay semantics**: session issuance, reuse, rotation, expiry, revocation and concurrency.
- Can provide a **non-secret protocol contract**: original command coverage `01 01` and Stage 1 `00 16`, `01 00`, `0A 00`, `1F 0C`, `09 00`, `00 00`; opaque compatible processor inputs/outputs; allowed authentication and reply routing; required transport headers; error and version handling.
- Can state whether the service's VIP subscription is mandatory and whether CGM membership could be integrated **by agreement**. Do not assume the service will waive or accept external entitlement.
- Can provide verifiable authority/permission for accessing third-party game servers and for use of the mobile Garden client.

**Do not send user credentials, captured request URLs, relay-session IDs, packets or modified APKs in the initial inquiry.**

### Route B — official PlayPark supported integration (strongest authorization provenance)

Seek written clarification from PlayPark about:
- Whether the intended **Garden-only mobile companion integration** is permitted at all.
- Who can authorize third-party mobile clients, private gateway access and official account authentication; whether a supported SDK/API/partner sandbox exists.
- Whether Mobile Garden and/or an independent CGM gateway is licensed or prohibited, and whether operator permissions are sufficient.
- Appropriate mechanisms for official identity proof and session issuance, including privacy requirements for credential-bearing transport.

Because published policy warns against unofficial modified clients, no live integration can be considered authorized based merely on technical viability or community reassurances.

### Route C — self-hosted replacement processor

**Blocked.** Current JPCG `public/session.php` forwards to the configured original intermediary; `public/authfirst.php` is membership-only; `ZeroTransformRelayCandidate` is an explicitly unproven offline demonstration. No permitted implementation of original **outgoing game-bound** packet preparation and real issuer/acceptance is available in inspected sources. A different unrelated Audition emulator or inverse of native incoming `PacketEncryption.Decrypt` is not a working substitute.

## Explicit provider acceptance criteria — for later review only

1. **Permission** documented by the relevant rights holder/service operator.
2. **Identity**: original account login authenticated by the legitimate provider, CGM membership checked against the corresponding verified principal, not only a typed claimed email.
3. **Original login**: preserves valid official login material while minimizing credential exposure; no plaintext or reversible protected GET URLs retained in logs.
4. **Session continuity**: handles fresh/re-login Stage 0, safe rotation/revocation/expiry, overlapping Stage 1 requests and official identity binding.
5. **Packet processing**: supports Stage 0 and all six observed Stage 1 commands using an authorized processor; no invented response data, fake userSN/CCU or forged sessions.
6. **Proof of acceptance**: provider attests legitimate LoginServer, GameServer and GardenServer responses/authorizations in an approved test. Neither `CGM ACTIVE` nor HTTP `error=false` is enough.

## Operator inquiry (suggested, NOT SENT)

> Hello, I'm researching a possible permission-based integration for a community mobile Garden companion and would like to contact the technical owner of Audition Mobile Garden. We have a separate membership system and want to preserve genuine PlayPark authentication and existing Garden-server authorization, without modifying or bypassing your service's membership controls. Do you offer a documented/licensed third-party integration or test environment for ANLGarden version 1026, and who handles such requests? We do not need to exchange account passwords or private packet captures at this stage.

## Publisher inquiry (suggested, NOT SENT)

> Hello PlayPark Support, could you direct this to the appropriate technical/partnership team? We are exploring a permission-based, Garden-only mobile companion for accounts that authenticate through PlayPark. Before attempting any integration, we would like to confirm whether third-party clients or a supported partner API/test sandbox are allowed, and which entity can formally authorize access to the official account and Garden services. We will not submit account credentials, tokens or game traffic in this initial inquiry.

## Engineering outcome

**Search completed, legitimate implementation NOT obtained**. Priority is *operator-supported interface discovery + publisher permission*, not further ciphertext-length inference, synthetic acceptance tests, APK rebuild or live probes.

No changes to `main`, backend or site routing, Nox, signed APK, user account credentials, or original PlayPark services.
