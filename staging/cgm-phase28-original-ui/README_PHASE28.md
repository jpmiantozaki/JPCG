# CGM Auth-First / ANLGarden — Phase 2.8 staged patch

**Date:** 2026-10-28. **Status:** source/archived-scene verified, **NOT Unity runtime tested**. Phase 2.8 is NOT marked passed. This is a reversible update for the existing Unity 2021.3.45f1 AssetRipper GardenUI project—not a new prototype and not a PlayPark login implementation.

## Exact changes

1. `Assets/Editor/CGMPhase28OriginalUIRenderProbe.cs` is **additive**. It inspects the active original GardenUI Game view and offers an in-Play-Mode swap of five existing PNG sprites. It does **not** edit the scene YAML, importer settings, `.meta` files or create replacement artwork. The source GUIDs in the recovered scene point to the matching `Assets/Sprite/*.asset` files; original `Assets/Texture2D/*.png` also exist. Whether the rebind **visually repairs** the black screen is unproven until Unity Play Mode screenshot evidence.
2. `Assets/CGMOriginalMembership/CGMOriginalMembershipGate.cs` **REPLACES the existing Phase 2.7 file at the same path**. Do not keep a separate Phase 2.7 copy. It retains the existing `CGM.OriginalUI` class names to avoid duplicate bootstrap/components, and waits until the existing original `LoginServerManager.Start()` initializes three offline button callbacks.
3. Yellow **LOGIN** remains the original offline panel-opening callback. Original **CONFIRM** launches only one **email-only** membership request to `http://127.0.0.1:18996/membership` and never invokes `Login()`/`SendLogin()`. **CANCEL** aborts the in-flight request, invalidates its generation and invokes the existing original CANCEL action once. Events are restored on component destruction if still owned by this adapter.
4. An ACTIVE result is displayed **only** for matching request ID, schema, `gateway_mode=LIVE_CGM_BACKEND`, plausible time/expiry and a positive TTL no greater than five seconds. INACTIVE, ERROR, offline fixture, timeout, malformed reply, stale/late callback, expired membership and gateway outage all fail closed. ACTIVE is an **email lookup only**, not proof of email ownership or an official authenticated account.
5. The project still lacks authorized game authentication, accepted CCU, GameServer and GardenServer sessions. No official tokens, passwords, fake packets or fallback authorizations are generated.

## Source prerequisite

- Existing recovered Unity project with original `Assets/Scenes/GardenUI.unity`, `Assets/Scripts/Assembly-CSharp/LoginServerManager.cs` (offline v4 manager) and **one** `Assets/CGMAuthPrototype/CGMRealMembership.cs` for the previously verified email-format check.
- Existing `Gateway/gateway_local.py` from `CGM_Custom_Port_Gateway_v1.zip`; do **not** replace/retest that working gateway.
- Windows Unity Editor; Android/Nox is outside this checkpoint. Rollback Nox `127.0.0.1:62030` must not be accessed.

## Install safely (after backing up the project)

1. Close Unity/stop Play Mode. Save a backup of the original project first.
2. Copy only these two `.cs` files to their exact relative paths above. The original membership gate `.cs` is intentionally replaced; **keep its existing `.meta` file**. Do not copy a second class into another folder. No other original source/scene/sprites are replaced.
3. Open `GardenUI.unity`. Verify Unity compiles before pressing Play. Do **not** infer a runtime pass from compilation alone.

## One focused visual and original-button run

1. Press **Play** in GardenUI. Run **CGM > Original UI > 2.8 - Diagnose Original Rendering (Play Mode)** and copy the `CGM 2.8 RENDER` lines only (these have no credentials). If original Yellow LOGIN is visible, capture a screenshot and click it to open `imgLoginBase`.
2. If the Game view remains black, run **CGM > Original UI > 2.8 - Preview Original PNG Sprites (Play Mode)**. This preflights all five original PNG Sprite imports before swapping them **only in Play Mode**. Observe whether the original login splash and yellow button appear. Then run **Revert PNG Preview** or exit Play Mode. This avoids saving an unproven fix to GardenUI.
3. Once the original login/credentials panel is visible, start your **existing** gateway in LIVE mode from your actual project folder (do not use `C:\Windows\system32` as a script location):
   ```powershell
   $project = Read-Host "Paste your actual Unity project path"
   py -3 "$project\Gateway\gateway_local.py" --mode live --port 18996
   ```
   The gateway must bind to **Windows** localhost. Do not enter a PlayPark game password into this offline prototype; original game InputFields are disabled.
4. Click original Yellow LOGIN, type **your own CGM membership email only into the separate CGM 2.8 panel**, and press **original CONFIRM**. Check LIVE ACTIVE expiry against JPCG admin (but **no official auth success**). Repeat once with an **unregistered** email to confirm LIVE INACTIVE and no progression. Click original CANCEL during an in-progress request; verify it resets and a subsequent request is possible.
5. If any visual/behavior test fails, STOP and report only `CGM 2.8 RENDER ...`/`CGM 2.8 READY` lines, UI screenshots, and errors with emails/secrets hidden. Do not declare Phase 2.8 passed or move to Android/official-login integration.

## Accepted/blocked conditions

| Condition | Expected effect |
|---|---|
| Original five images visible, Yellow LOGIN works | Visual gate candidate; take screenshot |
| Original screen black after temporary PNG preview | Visual gate blocked: inspect reported alpha, cull, bounds, sibling overlays |
| LIVE ACTIVE with valid server time, expiry, matching request ID | Show *CGM eligibility only*, no `Login()` call |
| LIVE INACTIVE or backend ERROR | Show rejection/error; no game login |
| Offline fixture (even if it says ACTIVE), missing mode or bad ID | Fail closed; not a real member result |
| Gateway timeout, panel closed, CANCEL, late reply | Abort/invalidate; safe retry |
| Official PlayPark/game/Garden auth | **NOT implemented / NOT verified** |

## Rollback

Stop Play Mode. Restore only the previous Phase 2.7 `CGMOriginalMembershipGate.cs` from your project backup and delete the new `Assets/Editor/CGMPhase28OriginalUIRenderProbe.cs` if desired. Keep all existing Unity asset GUIDs and `.meta` files. No database, PHP production route, upstream relay, or emulator is modified by this patch.

**Evidence boundary:** The archived recovered scene has 1,139 GameObjects. The five target Images reference `Assets/Sprite/intro - anl29.asset`, `login_btn_1-min.asset`, `logbase.asset`, `confirm_1.asset` and `cancel_1.asset`; the original corresponding PNG imports also exist. Those are **static facts**, not proof that Unity renders them correctly. Only a new original GardenUI runtime screenshot can close the visual issue.
