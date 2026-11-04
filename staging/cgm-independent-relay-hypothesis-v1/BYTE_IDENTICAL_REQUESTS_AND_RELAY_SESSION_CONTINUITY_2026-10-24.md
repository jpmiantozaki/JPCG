# CGM 1026 — byte-identical original requests across sessions and relay continuity

**Date:** 2026-10-24. **Scope:** read-only, offline user-owned October 8 archived access-log analysis; no external game requests, login attempt, Nox, APK change or production modification. Follow-up to `FRAME_COUNTER_VS_HTTP_ARRIVAL_AUDIT_2026-10-24.md`.

## New discriminating evidence

The six directly mapped GameServer senders identify two **zero-payload** Stage 1 commands:

- `SendRequestUserInfo`: `0A 00`, total frame exactly **8 bytes**, original frame counter **3** in each of three historical clusters.
- `SendPing`: `00 00`, total frame exactly **8 bytes**, original frame counter **6** in each of two clusters where observed.

The complete 8-byte original packet format is `u16_le(total length) || u32_le(session packet counter) || u8(cmd main) || u8(cmd sub)`. There is **no remaining payload** for either command. Thus equality of length, counter and command already entails equality of the entire original framed request for these cases.

As an additional independent check, a local, private offline inspection decrypted historical protected request envelopes and compared the **complete 8-byte client request frames** without printing or exporting them. All 20 archived `session.php` protected request checksums and frame-length matches passed. For UserInfo, **3/3** archived eight-byte framed requests were exactly identical. For Ping, **2/2** were exactly identical.

## Output lengths still vary for byte-identical inputs

| Command | 13:33:07 UTC | 13:33:32 UTC | 13:49 UTC |
|---|---:|---:|---:|
| UserInfo `0A 00`, counter 3, exact 8-byte frame | 17 (delta +9) | 13 (+5) | 14 (+6) |
| Ping `00 00`, counter 6, exact 8-byte frame | Not observed | 17 (+9) | 15 (+7) |

**Conclusion:** A *stateless deterministic transformation of the framed original request bytes alone* cannot explain the observed varying relay output **lengths**. Additional processing context or a nondeterministic element matters. This does **not** prove a cryptographic algorithm or establish that the same framed input would vary under the **same** full relay/session context.

## Mobile relay-session context — private equality-only analysis

Using the original SecurePacket format from JPCG `public/_secure_packet.php`, the existing user-owned archived access-log files `Pasted text(3).txt` (two 13:33 UTC clusters) and `Pasted text(4).txt` (13:49 UTC) were inspected **locally only** for the original plaintext `packetHex~stage~relaySession` envelope. This local investigation produced **only boolean equality/distinct-count conclusions**; account strings, password fields, packet payloads, raw protected GET URLs, frame bytes, encryption keys and relay-session strings were neither printed nor committed.

- **13:33:07:** six request records, Stage 0 has nonempty incoming mobile relay identifier; **five** Stage 1 requests all carry the **same** relay identifier; Stage 0 incoming identifier does **not** equal the Stage 1 identifier.
- **13:33:32:** seven records, Stage 0 has nonempty incoming mobile relay identifier; **six** Stage 1 requests all carry the **same** relay identifier; Stage 0 incoming identifier does **not** equal the Stage 1 identifier.
- **13:49:** seven records, Stage 0 has **no** incoming relay identifier; **six** Stage 1 requests all carry the **same** relay identifier.
- Across the three clusters, the Stage 1 relay identifiers are **distinct** from one another. For the repeated, byte-identical UserInfo/Ping frames, the complete HTTP relay envelopes therefore **are not identical**, even though their embedded 8-byte original client frame is identical.

The original **response-side** `MGRelayResponse.session` values were **not available as plaintext in these logs** and were not compared. The evidence therefore does **not** prove the upstream issued the subsequently observed Stage 1 identifier in its Stage 0 response, even though that is consistent with the known Unity callback behavior; no issuer/authenticity proof follows from Stage 1 token reuse.

The earlier Stage 0 incoming relay identifiers may represent pre-existing mobile relay state; the diagnostic record does not identify their origin or corresponding login session. Do not conclude they are valid provider-issued identities or authenticated tokens.

## Engineering implications

1. **Do not implement a deterministic packet-only transformer** derived from length, opcode, counter, or original frame bytes: actual BASE-origin reply lengths disprove it for zero-payload commands.
2. **Do not infer intrinsic randomness** from those results. Per-session context already differs across traces, so a deterministic function of `packet + authorized relay state` remains compatible with the observations. Random padding, nonce, stateful cipher, per-session key, or other transformations remain **unproven alternatives**.
3. **Session scope must be explicit in a legitimate compatible provider integration.** The original client sends the same relay identifier across its Stage 1 requests within each historical cluster; a robust authorized integration would need genuine provider-issued/bound continuity, not CGM-invented login/CCU tokens.
4. **Stage 0 session-presence compatibility is not settled.** Prior staging `CgmClientPacketInspector` rejects nonempty Stage 0 relay sessions, yet two observed BASE-origin Stage 0 requests included them. Keep that parser's fail-closed behavior for staging until original provider semantics and safe reauthentication are established; do not silently accept or treat a preexisting relay identifier as trusted.
5. **Do not mistake relay HTTP `error=false` for game-server acceptance.** Stage 1 transport continuity and Garden authorization remain distinct. This analysis reveals no authorized server-side outbound processor, response-byte algorithm, game-account identity proof, or independently accepted CGM login.

**Next discriminating evidence:** lawful/authorized intermediary source or approved environment with attested provider-issued session continuity and original server acceptance, plus privacy-reviewed transformed-packet framing metadata. No need to repeat synthetic test suites or ask for another APK/Nox run.

**No production code modified.** All sensitive source data stayed local to the private analysis and is excluded from this repository checkpoint.
