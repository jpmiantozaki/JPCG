# Phase 3.3X — Capture-validated GardenServer mode-B transport

Date 2026-10-29. **Experimentally recovered Garden transport; this is NOT an account/session issuer.**

## Material results

- Original `Audition_unprotected_extracted.exe` SHA256 `a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911`; Ghidra `FUN_009E0CF0` builds alternative packet frames. Verified `FUN_009DE2E0` / `FUN_009DE1E0` encode/decode the 3-byte size header. `FUN_009DE240` / `FUN_009DE120` use a different 256-byte lookup plus one selector byte for the body. Original table and executable binaries are **not committed**.
- Two alternate send functions: `009E0170` (example caller `00A72790` pushes slot 5) and `009E01C0` (example caller `00A7D6DE` pushes slot 7). Receive dispatcher `009DFAE0` uses special `009E0B60` branch for 5 and 7. **Exact remote endpoint-to-slot mapping is still open.**
- Read-only TCP reassembly of user-provided `test2.pcapng` and `cgm(1).pcapng` on captured Garden port `20840`: **61 frames** accounted for with **zero trailing bytes and zero detected sequence gaps**. **55** full payloads decoded; **6** large frames have only headers/known prefixes. **All 61 first payload bytes decode to marker `0xC8`** (including prefixes of the six large frames). Captured packet contents never printed, written, or committed.
- The single previously unmatched 800-byte GameServer candidate on `18806` did not match the alternative frame test; don't conflate the two transports.
- Added pure bounded staging PHP decoder `src/CgmGardenModeBPacket.php`, accepts separately provisioned lookup tables and fails explicitly to `PREFIX_ONLY_UNVERIFIED_LONG_BODY` when original native accesses exceed the 256 initialized lookup bytes. This is not wired to `public/session.php`, any live service or user account.
- `tests/garden_modeb_packet_test.php` **1,402 synthetic assertions PASS**. PHP/Python private offline comparison of original 61 captured frames also **61/61 PASS**, `55 FULL` + `6 PREFIX_ONLY`. The independently executed Python file has five passing test groups, including real original PE callsites. Complete scripts and privacy-safe aggregate reports are delivered as conversation ZIP `CGM_Phase33X_GardenServer_Codec_Stream_2026-10-29.zip`.

## Next high-value target

1. Match Ghidra socket slot 5/7 to exact network endpoints, and follow Garden packet dispatcher past the `0xC8` transport marker to actual command/field layout.
2. Resolve larger-body read behavior in `FUN_009E0B60` and `FUN_009DE120` (six original Garden frames of 239,277,345,530,5357,5622 plaintext bytes currently prefix-only). Never silently fill missing lookup bytes.
3. Integrate the protocol parser into an independently implemented upstream **only after** actual account/session handling is established; transport decoding alone does not create accepted PlayPark login or Garden authorization.

No official game credentials, tokens, raw packet bodies, tables, EXE, PCAPs or IPs in repo. JPCG `main`, production Render/Neon, original APK, Nox62033 and rollback Nox62030 remain unchanged.
