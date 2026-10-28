# Phase 2.8.2 — Original GardenUI visibility recovery (staging only)

**Status:** static source checks 22/22 PASS; **Unity runtime NOT YET VERIFIED**. Do not mark Phase 2.8 completed.

## Evidence (user runtime + archived scene)
- 2026-10-28 Play Mode: `scene=GardenUI`, active ScreenSpaceOverlay Canvas, scale `(1,1,1)`, target bounds on screen.
- Five original login Images report `colorAlpha=0.000`, `sprite=NULL`, and `renderer=NULL`. `imgLoginBase` is deliberately inactive before Yellow LOGIN.
- Archived `GardenUI.unity` serialized the five Images with white alpha=1, nonzero Sprite references, and CanvasRenderer components.
- The custom membership panel is `CGMLocalPortProbe.OnGUI()`, **not** proof the original UGUI Canvas renders. It overlaps the Yellow LOGIN screen region.

## Reversible v2.8.2 diagnostic
Replace only `Assets/Editor/CGMPhase28OriginalUIRenderProbe.cs` in the **user's existing Unity project**; preserve its .meta file.
1. Stop Play Mode, replace the old diagnostic C# file, then enter Play Mode in `GardenUI.unity`.
2. `CGM > Original UI > 2.8.2 - Preview Visible Login (Play Mode)`: preflight five original PNGs; snapshot Image sprites/color/enabled; set original PNG sprites, white alpha, and add CanvasRenderer only where missing.
3. `2.8.2 - Hide Custom Gateway Overlay (Play Mode)`: optional, disables only the local probe's IMGUI component (gateway backend untouched). Useful because its box overlaps Yellow LOGIN.
4. `2.8 - Diagnose Original Rendering (Play Mode)`: collect `CGM 2.8 RENDER APPLY`, `PREVIEW_APPLIED`, and target lines + Game screenshot.
5. If still black, `2.8.2 - Toggle UGUI Canary (Play Mode)`: temporary magenta RawImage top-right; visible=Canvas can draw UGUI, absent=investigate Canvas/graphics pipeline.
6. `2.8.2 - Revert Visible Login Preview (Play Mode)` or exit Play Mode; restores original Image values, removes only added renderer components, and restores probe enabled state.

No scene save, sprite importer changes, gameplay credentials, PlayPark auth, game/CCU/Garden session, or rollback emulator operations.

**Stop** on Unity compile error, `PREVIEW_BLOCKED`, or unresolved black screen. A working preview is a **visual-only** result; live CGM membership through original buttons still requires a separate test.
