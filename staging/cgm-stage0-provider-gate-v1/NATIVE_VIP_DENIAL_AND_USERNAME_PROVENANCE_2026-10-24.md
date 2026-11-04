# CGM Stage 0: native client identity provenance + negative VIP response

**2026-10-24.** Offline-only follow-on to user's Windows **66/66 PASS** for Stage0ProviderGate. No legacy \`session.php\` route or production config changed. **New runnable source is committed** at \`src/NativeVipDenialReply.php\` with standalone \`tests/native_vip_denial.php\`, rather than another downloadable ZIP.

## New original Unity / IL2CPP evidence

Original ARM64 \`libil2cpp.so\` SHA256 \`fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8\`; native offsets mapped to existing \`dump.cs\` method names. These are observations from offline \`llvm-objdump\`, not PlayPark server evidence:

1. \`LoginServerManager.SendLogin\` (\`0x6A80C0\`), branch at \`0x6A8194–0x6A81B8\`: chooses \`INPP.\` or \`INFB.\` prefix and writes the prefixed login ID in its first safe-string field. At \`0x6A821C–0x6A823C\`, stores that derived account ID in the client \`AUServerInfo.userInfo\` shared string at native offset \`+0xB0\`.
2. \`RelayWebManager.<RelayServerData>b__11_0\` (\`0x6B088C\`), at \`0x6B09F0–0x6B0A1C\`, reads shared username and passes it to \`VIPManager.ReceiveNoVIPData\` (\`0x6B8AEC\`) in the \`VIP\` rejection branch.
3. \`VIPManager.ReceiveNoVIPData\` at \`0x6B8B78\` stores the original passed username in \`szUserNameVIP\` (object offset \`+0x40\`). It separately strips the first **five characters** for display near \`0x6B8BD8–0x6B8BF0\`. The displayed shorter string is **not the same as the stored account ID**.
4. \`VIPManager.SubmitRedemption\` at \`0x6B8AD4–0x6B8AE4\` loads \`szUserNameVIP\` and \`szRedemptionCode\` together from offsets \`+0x40/+0x48\` and branches to \`RelayWebManager.RelayRedeemVIP\` (\`0x6B02C0\`) with the **stored** username. \`RelayRedeemVIP\` concatenates its username and code, and wraps the redemption payload using SecurePacket.
5. On the current JPCG backend, \`public/redeemvip.php\` stores the submitted username verbatim, trimmed and case preserved; \`public/verify.php\` looks up an exact login ID by default. \`authfirst.php\` is a separate experiment with possible plain/\`INPP.\` fallback, which is **not** proof of ownership or alias equivalence.

**Engineering conclusion:** For the original native redemption route where \`ReceiveNoVIPData\` supplied the username, the redemption request uses the *original prefixed username* rather than the five-character-stripped display form. Exact \`INPP.\`/\`INFB.\` case-preserved Stage 0 matching therefore aligns with the observed native flow. Historical plain-email records may arise from other routes; their ownership cannot be assumed or silently aliased.

## Actual new code: safely complete the denied Stage 0 path

Original client \`RelayWebManager.<RelayServerData>b__11_0\` checks \`MGRelayResponse.error\`, then the exact message \`VIP\` to open the original VIP/redeem UI. The prior staging \`CgmStage0ProviderGate\` returned a metadata-only \`CGM_MEMBERSHIP_NOT_ELIGIBLE\` decision **without an encrypted client-visible result**; a future integrated transport would otherwise need a compatible denial reply.

New dependency-free \`CgmNativeVipDenialReply::encode($decisionMetadata, $encrypt)\` is deliberately narrow:

- For **only** \`status=CGM_MEMBERSHIP_NOT_ELIGIBLE\` with all four fail-closed metadata flags false, it constructs \`{error:true,message:"VIP",data:"",servertype:0,session:"",vip_expiry:0}\` and invokes the provided original-compatible \`cgm_encrypt_packet\` encoder.
- For any active/provider-accepted, upstream-rejected, invalid, incomplete, or contradictory decision metadata, it returns null. It does **not** rewrite upstream rejections.
- It cannot create \`error=false\`, a LoginServer/GameServer packet, relay session, CCU, an authenticated principal or Garden authorization.
- The encrypted \`VIP\` response is **CGM's own membership denial**, not a response from the original provider. Do not attribute it to the original BASE/MOD origin.

## Local verification (performed in assistant environment, NOT user Windows)

- Standalone repo-ready test \`tests/native_vip_denial.php\`: **19/19 PASS** with synthetic fixture envelope; PHP syntax PASS.
- Additional private offline checks using an implementation copied from JPCG's current \`public/_secure_packet.php\`: **30/30 randomized SecurePacket encodes/decodes** yield \`UPSTREAM_REJECTION_SHAPE\` and exact \`vip_marker=true\` in the previously tested response inspector. This test name is about shape classification, not actual server origin.
- Composed test: synthetic original-framed Stage 0 login encrypted using JPCG's SecurePacket; inject \`INACTIVE\` membership; \`CgmStage0ProviderGate\` does **not** call the provider; new encoder emits protected native-shaped VIP rejection; existing response inspector recognizes it. **PASS**.
- Prior Stage 0 regression: **ALL 66/66 PASS** locally. User already independently confirmed this suite on Windows; the new 19 checks have **not** been independently run by the user and do not need to be unless a real integration requires it.

Run the new standalone test from a repository checkout with PHP 8.1+: \`php staging/cgm-stage0-provider-gate-v1/tests/native_vip_denial.php\`. No ZIP needed.

## Deployment / authenticity boundary

**NOT DEPLOYED.** \`public/session.php\` still delegates to \`public/_cgm_session_bridge.php\`. The new source is a staging helper, not a complete replacement; Stage 1 remains unsupported by \`Stage0ProviderGate\`.

The material blocker for CGM-only accepted game sessions remains an **authorized compatible session processor**, true official authentication and provider-issued relay continuity. A negative CGM VIP denial can be implemented from the existing client contract; an authenticated positive response cannot be legitimately manufactured from that contract alone. Any future live wiring must review Apache/proxy query logging and protected credentials, exact-account identity binding, legitimate provider consent and Stage 1 commands. **No APK, Nox, game server or public JPCG endpoint touched.**
