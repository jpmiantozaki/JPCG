#!/usr/bin/env python3
"""Static inspection of NEW Phase 2.8 code only; not a Unity runtime test."""
from pathlib import Path
root = Path(__file__).resolve().parents[1]
core = (root / "Assets/CGMOriginalMembership/CGMOriginalMembershipGate.cs").read_text()
render = (root / "Assets/Editor/CGMPhase28OriginalUIRenderProbe.cs").read_text()
checks = {
    "POST membership path": '"/membership"' in core,
    "schema and request correlation": 'result.request_id != id' in core and 'result.schema != Schema' in core,
    "only LIVE ACTIVE provenance": 'result.gateway_mode != "LIVE_CGM_BACKEND"' in core,
    "offline fixture segregated": 'OFFLINE_FIXTURE_NOT_BACKEND' in core,
    "timeout fail closed": 'request.timeout = 28' in core and 'Time.realtimeSinceStartup - startedAt > 35f' in core,
    "cancel generation and abort": '++generation' in core and 'inFlight.Abort()' in core,
    "expiry and ttl validation": 'result.membership_expires_at <= localTime' in core and 'result.ttl_ms > 5000' in core,
    "no original login continuation": 'savedConfirmEvent.Invoke()' not in core and 'SendLogin(' not in core,
    "no PlayPark password fields in request": 'public string password;' not in core and 'public string username;' not in core,
    "no persistent importer mutation": 'SaveAndReimport' not in render and 'EditorSceneManager.SaveScene' not in render,
    "five original PNG targets": all(x in render for x in ('intro - anl29','login_btn_1-min','logbase','confirm_1','cancel_1')),
    "preview reversible": 'void Revert()' in render and 'originals.Clear()' in render,
}
for name, ok in checks.items():
    print(("PASS " if ok else "FAIL ") + name)
if not all(checks.values()): raise SystemExit(1)
print(f"NEW PHASE 2.8 SOURCE CONTRACT: {len(checks)}/{len(checks)} PASS (STATIC ONLY)")
