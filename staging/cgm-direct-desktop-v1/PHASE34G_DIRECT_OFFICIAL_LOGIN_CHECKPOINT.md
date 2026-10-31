# Phase 3.4G — Direct official TCP desktop Garden, CGM membership preflight

**October 31, 2026 — Source examination and offline integration. NO live game login performed by assistant.**

## What changed

The user's NEW `garden_tool (7).py` (SHA256 `ce5071114a3654a1e92b5cb099f397d8ad32fee672b8a4819f35b0f82c436f49`, 875 Python lines) is NOT the earlier Phase 2.8.6 `garden_tool(1).py` stub. Unlike the stub, it contains complete capture-derived GameCipher (ports 18805/18806), GardenCipher (port 20840), LoginReply, GameServer handshakes, Garden C8 requests and a functional two-window viewer. **The user reports official credentials were accepted during their own live run.** The assistant did not reproduce a live login or touch user credentials.

The NEW direct-client architecture is:

`CGM ACTIVE email-only preflight (existing JPCG /authfirst.php)` -> `user-entered official game credentials remain on local desktop` -> `direct official LoginServer TCP 18805` -> `GameServer TCP 18806` -> `Garden TCP 20840` -> `C8/02 Garden data displayed`.

**This desktop transport does not use the original third-party mobile `session.php` relay.** It does not issue fake official tokens or translate an upstream VIP failure into success. It is NOT yet a replacement for the original Unity IL2CPP APK.

## Confirmed corrections in the downloaded complete Windows source package

1. Original `GameCipher.encrypt` selected signed `randint(-7,7)`, occasionally creating an undecodable 0xFF header. In **500 deterministic offline cases, 116 failed**. Correct native valid lengths are L=2..9; updated cipher passes **2,000/2,000** deterministic round trips and was independently checked against archived traffic.
2. Original `LoginReply` checked only opcode `p[2]==1` and could parse a fabricated `01/01/06` failure as successful-looking. Fixed to demand `01/01/00`, bounded lengths, and never dump token bytes in error text.
3. Original Garden 17-byte record map displayed growth points (+12) as water. Correct water is +8, growth level +11, growth points +12.
4. Original viewer misrepresented 18-byte separate slot state records as Secret Garden plants. Corrected: SG remains explicitly NOT DECODED.
5. Corrected dropped partial GameServer receive frames, validated Garden frame completeness and original C8/00/00 acknowledgment before C8/02 display, cleared password/PIN Tk entry fields at submit.
6. Reused the **already-tested Phase2.8.6** CGM `cgm_membership.py` and `cgm_attempts.py`; no new backend or duplicate entitlement database. Sends only email/request_id/schema to JPCG, verifies response freshness, correlation, ACTIVE/unexpired TTL.
7. Optional local `catalog.csv` supports item_id/name mapping; missing catalog remains numeric IDs. Earlier Phase4.10 recovered a complete 496-item catalogue in `couplegarden.txt`, but it was NOT silently fabricated here.

## Locally executed tests

- **25/25** new Python unit tests, including 2,000 original-game cipher and 500 Garden cipher deterministic synthetic round trips, refusal of rejected LoginServer result, C8/02 full 5,355-byte shape, water-versus-growth field mapping, stale membership, and verification-before-game-socket ordering.
- **32/32** reused Phase2.8.6 membership-only tests (outdated original-stub-specific tests intentionally omitted).
- **8/8** new source-independent GitHub-style CGM preflight tests.
- **2/2** real Tkinter GUI Xvfb smoke tests, including password/PIN widget clearing.
- **11/11** original *PC* `test2.pcapng` selftest assertions PASS (offline only), including captured handshake, token order, first nine Garden requests and parsed C8/02.
- Original PC `test2.pcapng`: 219 Game/Login frames and 36 Garden frames fully parsed, showing 80 plants, 5 placed items, 37 inventory entries.
- Archived Android/Nox-labelled `cgm(1).pcapng`: 27 Game/Login frames and 25 Garden frames fully parsed, showing 80 plants, 22 placed items, 41 inventory entries. Zero inferred SG entries.
- Downloaded full code ZIP `CGM_Phase34G_Direct_Official_Garden_Desktop_2026-10-31.zip`, SHA256 `296f6d1e3864c5650081b7d89eef810eb49dd756547598a25f9e2b16b37dbb4e`, 40,206 B and 17 entries. ZIP CRC, each entry SHA256 and clean extraction tests verified. Contains complete corrected desktop client and launcher.

## GitHub scope

This feature branch **stages only** the previously vetted JPCG membership preflight modules, tests, and checkpoint. **It does not publish** the user's game-specific Python file with reverse-engineered lookup tables, historical endpoint IP addresses, session tokens, or game captures. The complete patched Python source is in the user-downloadable ZIP as a local artifact.

## Important security and product limits

- The direct original-game TCP protocol uses **reversible XOR**, not authenticated TLS. A password captured by a passive network observer could be decoded. Do not use untrusted Wi-Fi or share packet captures.
- Existing JPCG `authfirst.php` is an **email-claim membership check**, not authenticated official PlayPark account ownership. Client-side preflight is not a secure anti-tamper entitlement proof.
- Source `garden_tool (7).py` obtains login/Game/Garden tokens from the official server reply and sends them directly, not from JPCG. An archived PCAP handshake is not a reusable live session.
- Hard-coded host fallback and GameServer resource check were copied from a previous capture and may be version-dependent. We did **not** test the modified client against live game servers here.
- The **original CGM Android APK** is still Unity 2021 IL2CPP with outgoing `RelayWebManager.SendRelayInfo` HTTP Stage0/Stage1 and cannot import Python directly. A later direct-socket mode port would require matching Unity/C#/native code and a separate signed disposable-device build; **none was produced here**.
- Original third-party VIP policy, PlayPark decisions and server entitlement checks remain distinct. Don't fake these decisions.
- Secret Garden, Hearts/Gifts/Den drops and action/write commands are not included.

## Next Phase 3.4H

1. User can perform an optional, local **self-authorized** test of the downloaded desktop viewer; share **status labels only**, never credentials, PCAPs, token bytes or raw packet bodies.
2. To reach CGM Android, create and validate a compatible direct TCP transport layer for the original Unity runtime, preserving its own Login/Game/Garden response handlers and ensuring the CGM membership preflight runs before credential transmission exactly once. The original APK must be rebuilt/tested separately; the desktop Python cannot simply be copied into `libil2cpp.so`.
3. For real membership enforcement rather than a client-only convenience gate, link to a truly provider-verified official account principal; claimed-email ACTIVE is not authoritative.

**Unchanged:** JPCG main/Render, original signed APK, Nox62033, rollback Nox62030, official game infrastructure. 
