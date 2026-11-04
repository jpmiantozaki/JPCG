# CGM 1026 — seven original BASE packet-length pairs vs native inbound framing

**Date:** 2026-10-24  
**Finding:** Seven paired **one-session** successful BASE-origin request/response length records have deltas that all fall in the Unity client's **3–12 byte** variable incoming-packet framing overhead range. This materially improves the evidence for a *packet-framing/encryption hypothesis*, not a demonstrated server-side encryption algorithm.

## Exact provenance and scope

User-owned archived October 8, 2026 trace in Library `Pasted text(4).txt` and its copy in `CGM-Conversation-Backup-2026-10-08.txt`. Selected **only** `CGM_SESSION_REQUEST` and `CGM_SESSION_RESPONSE` redacted JSON diagnostics from the **13:49 UTC** fresh BASE VIP + CGM ACTIVE run. Matched adjacent request and response events and cross-checked the response's embedded `request_stage` and `request_packet_bytes` against the preceding request. All origin-labeled responses said `upstream_host=anlgarden.com`, HTTP 200, `error=false`, 12-byte intermediary relay session, and valid hex for `data`. Note: this was **one historical session with seven packet exchanges**, not seven independently authenticated logins. Raw encrypted GET URLs and packet/credential/token material in some archived logs were **never copied into any committed artifact**.

| Seq | Stage | Original request bytes | Original relay `data` bytes | Delta | Hypothetical k = delta - 3 |
|---:|---:|---:|---:|---:|---:|
| 1 | 0 | 58 | 63 | +5 | 2 |
| 2 | 1 | 16 | 21 | +5 | 2 |
| 3 | 1 | 16 | 28 | +12 | 9 |
| 4 | 1 | 8 | 14 | +6 | 3 |
| 5 | 1 | 14 | 26 | +12 | 9 |
| 6 | 1 | 9 | 20 | +11 | 8 |
| 7 | 1 | 8 | 15 | +7 | 4 |

**Seven out of seven** have delta in `[3,12]`; **five distinct deltas** refute a universal fixed five-byte addition.

## Native reverse-engineering cross-check

Original shared BASE/MOD ARM64 `libil2cpp.so` SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`.

`PacketEncryption.Decrypt(byte[])`, RVA `0x69974C`:
- At `0x699794`, reads original received packet byte offset 2.
- `0x6997B4–0x6997C4` derives a header-dependent `k` with arithmetic/bit operations and validates `k <= 9`.
- `0x6997A4–0x6997D0` computes output length as original incoming `srcLen - 3 - k`. Therefore a packet processed by this incoming decoder can have 3 to 12 framing/encryption overhead bytes.
- No original *server-side* `session.php` encryption algorithm or legitimate packet issuer was obtained. Client's managed `PacketEncryption` class exposes `Decrypt(byte[])` and `Decrypt(int)`, **not** a matching client-side `Encrypt(byte[])` method. This absence in compiled client metadata does not rule out a server implementation.

### Interpretive limitations

1. The archived `data` is what the HTTP intermediary returns for **outgoing-to-game** packets. The inspected native decoder processes **incoming-from-game** packets. Matching overheads **suggest a related framing scheme but do not establish the two directions use the same algorithm**.
2. Only packet lengths and general response shape are present. We lack the returned response-packet **bytes**, including the particular header value that would allow checking whether the measured `k` matches `0x6997B4`'s header arithmetic. No actual decrypt/roundtrip of a BASE-success packet occurred.
3. Archived `servertype=null` means the diagnostic field was missing/unavailable, **not** that original positive responses necessarily had no usable routing field; the native client can apply defaults.
4. No reliable command-byte capture accompanied this trace, so Stage 1 rows are **not assigned to exact GameServer commands** from lengths alone. A request length of 8 could be either Ping or UserInfo, for example.
5. The only direct evidence of original game acceptance remains the historical user's Garden outcome in that BASE-eligible run. We cannot extrapolate independent CGM-only official login or working transformation code from that.
6. A 3–12 byte length delta is a **necessary size relationship** for this particular *hypothesis*, not proof of cryptographic correctness, server support, or compliant player access. **Do not implement guessed padding/encryption or synthetic positive responses into production.**

## New source committed (no user download required)

- `src/PacketOverheadEvidence.php`: CLI-agnostic, **read-only** metadata assessor, strict input allowlist to reject raw packet/url/credential fields, no network, no decryption and no session issuance.
- `fixtures/base_success_lengths_2026_10_08.json`: only the above seven redacted metadata pairs; no account identifiers, ciphertext, URLs, tokens, session values or packet hex.
- `tests/packet_overhead_evidence.php`: **20/20 PASS** locally with PHP 8.4, plus PHP lint PASS for source and test. Test excludes incorrect pairings, other origins, denied results, secret-bearing extra fields and out-of-range deltas. Tests are synthetic validations of the analyzer using historic sanitized lengths; they are **not** original server acceptance tests.

Command in an existing complete repository checkout: `php staging/cgm-independent-relay-hypothesis-v1/tests/packet_overhead_evidence.php`.

**Next evidence gate:** Obtain a properly authorized, privacy-preserving transformed-packet header/framing observation *linked to accepted game service behavior*, or the permitted original intermediary implementation. A header-only decoded framing witness plus a paired request could distinguish the native `3+k` layout from another 3–12-byte scheme without disclosing credentials or full packets. Such a test must be authorized and designed to avoid credential-bearing GET logs or exporting session material. Do not request new Nox/APK testing or touch the rollback environment solely on the basis of this length match.

**Production `public/session.php`, APK, Nox, and live original game servers remain untouched.**
