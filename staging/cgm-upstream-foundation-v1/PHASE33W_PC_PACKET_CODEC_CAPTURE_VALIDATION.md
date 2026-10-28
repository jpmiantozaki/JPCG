# Phase 3.3W — Windows Audition PC packet codec, capture-validated

**2026-10-29 — independent upstream staging, no production or live game changes.**

## Verified executable code flow

- Exact PC executable: `Audition_unprotected_extracted.exe`, base `0x00400000`, SHA256 `a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911`.
- Original `0x009E0050` sends via `0x009E0FE0` (frame adjust) and `0x009E0EF0` (scramble wrapper) -> `0x009E0740` (XOR/rotated-key cipher) -> socket vtable +0x1c -> `0x00A7F3F0` -> `0x00A7F410` -> Winsock `send()`.
- Reverse transform `0x009E0580` is called by receive wrapper `0x009E0E80`. The one verified mode stores a random **2-to-9-byte key in each packet**. It is not a constant key extracted once from a 167-byte initial reply.

## Independent structural evidence (no decoded contents stored)

Read-only metadata audit of two original user-supplied PCAPNG captures:
- `test2.pcapng`: 202 of 203 complete LE16 candidate frames on `18805/18806` decode to an inner packet whose own LE16 length exactly matches.
- `cgm(1).pcapng`: 27 of 27 such frames match.
- **Overall 229/230**. The single unmatched 800-byte `18806` inbound candidate has a non-codec-A header; classification remains unresolved. Frames split across TCP segments are outside this bounded test.
- Client-to-server initial `18805` packets: original decoded frame lengths **57** and **55**, packet counter **1**, command **`01 01`**, followed by precisely **three one-byte-length-prefixed fields**. This matches previously mapped mobile Stage0 plaintext *structure*, not verified official session equivalence.
- Incoming `18805` messages: scrambled lengths **167** and **170**, both decode to an inner **161-byte** packet with different per-packet key overhead.
- Garden `20840`: only **1 of 65** TCP segment starts even has a plausible header for this codec, so Garden framing remains separate.

## Upstream source and tests

- Added `staging/cgm-upstream-foundation-v1/src/CgmPcPacketCodec.php`, a pure-PHP encoder/decoder with strict length checks, secure random ephemeral key generation and no accounts, sessions or network code. Uses a synthetic key only in deterministic tests.
- Added `staging/cgm-upstream-foundation-v1/tests/pc_packet_codec_test.php`, **179** synthetic assertions including two cross-language expected vectors, 144 frame roundtrips, rejection tests and 30 secure-key fixture roundtrips.
- Supplemental downloadable `CGM_Phase33W_PC_Cipher_And_Upstream_Code_2026-10-29.zip` includes the independent Python reference, 15 executable host checks including exact EXE relative call targets, a metadata-only PCAP verifier, and PyGhidra follow-up script (syntax-checked, GUI run pending).
- No proprietary executable, original PCAP, password, original token or decrypted packet is committed or embedded.

## Next exact target

Phase3.3X: classify remaining alternative packet mode and GardenServer `20840` payload framing, then relate the proven PC `01 01` packet format to original APK Stage0 and the **genuine** relay/session processor. This codec is only the original PC transport layer, not yet a complete independent session issuer or authorized PlayPark/Garden login.

JPCG `main`, Render/Neon, original APK and Nox remain unchanged; rollback Nox `62030` never accessed.
