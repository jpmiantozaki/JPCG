# CGM / MoveOn Audition PIN demonstration — video review (2026-10-24)

## Source and scope
User uploaded a 30.833-second MP4 (426 x 212, H.264 video). Examined representative frames at 2-second spacing and the first 5 seconds at 0.25-second spacing. The video appears to demonstrate the MoveOn Audition Couple Garden interface. The demonstration's APK build/version, backend origins, authenticated server state, and relationship to current BASE/MOD 1026 are **not** independently established. No video/private data was committed.

## Visual sequence actually shown
- t=0–3.0 s: PIN-entry keypad and masked PIN value; confirm clicked around 3.0s. No raw PIN value retained.
- t≈3.2 s: “Verifying PIN...” dialog.
- t≈3.5 s: “Fetching Account Data...” dialog.
- t≈3.75–3.9 s: “Connecting to Garden Server...” dialog.
- t≈4.0–5.0 s: Couple Garden loading transition and displayed Garden.
- t≈6–30 s: Various Garden menus, shop/items and interaction, establishing visually that Garden interface was usable in the recording.

The clip begins with the PIN screen. It **does not show** Yellow Login, PlayPark credential submission, CGM VIP decision, LoginServer and GameServer protocol exchanges, or GameServer acceptance before the PIN screen. Network traffic and authenticated session origin cannot be inferred from frames.

## Comparison with existing static original-client findings
The visual sequence closely matches the compiled `PinManager.<VerifyPin>d__25.MoveNext` -> `PinManager.<FetchData>d__26.MoveNext` -> account fields -> `GardenServerManager.ConnectToGardenServer` code path. It is stronger UI evidence for that branch than method names alone. This does not prove the shown video is the same APK build or exact original 1026 route. The independent normal client path via GameServer Packet09 -> Garden also exists.

## Engineering implications
- Honor user's target normal flow: Yellow Login -> login input -> CGM ACTIVE mobile VIP preflight -> original official Login/GameServer -> optional PIN (if enabled/applicable) -> Garden. This is a **target/user-observed ordering**, not a full network-callback trace from the video.
- Keep the PIN feature outside the core CGM-only membership/session-processor investigation. Do not assume all users must do it.
- No provider-issued userSN/CCU, PIN/session key, or login response can be manufactured from a screenshot. Reusing tokens or treating this video as authenticated server acceptance evidence is not supported.
- Do not conflate independent web/PIN account fetch with the normal GameServer continuation or original mobile `session.php` stage 0/1 relay.

**Outcome:** Pin-to-Garden visual sequence confirmed; pre-PIN GameServer timing remains unverified in this clip. No APK/emulator/live server/production changes.
