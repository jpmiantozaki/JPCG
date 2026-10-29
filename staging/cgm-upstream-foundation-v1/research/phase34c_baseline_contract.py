#!/usr/bin/env python3
"""Phase 3.4C: fail-closed check of previously SANITIZED archive metadata.

This does NOT decode live/game traffic or independently authenticate a user.
Actual full offline PCAP/ELF analyzer and 31 synthetic tests are in the
downloadable Phase34C source ZIP; no original binaries/captures on GitHub.
"""
from __future__ import annotations
import json
from pathlib import Path

STEPS = (
    "LOGIN_SUBMISSION", "LOGIN_ZERO_RESULT", "VERIFY_CCU_REQUEST",
    "GAME_LOGON_REQUEST", "GAME_ZERO_RESULT", "GARDEN_REQUEST",
    "GARDEN_LOGIN_ZERO_RESULT", "GARDEN_LOAD_ZERO_RESULT",
)
ALLOWED = frozenset((
    "archive_provenance", "historical_recording", "frames",
    "total_frames", "single_client_network_address",
    "single_tcp_connection_per_service", "checkpoint_relative_seconds",
    "tcp_sequence_gaps", "unconsumed_stream_bytes",
    "stale_one_byte_overlap_candidates", "apk_process_identity_proven",
    "official_provider_account_bound", "new_cgm_mobile_session_issued",
    "cgmonly_vip_accepted",
))
PORT_DIRECTIONS = ("18805_C2S","18805_S2C","18806_C2S",
                   "18806_S2C","20840_C2S","20840_S2C")
LABELS = frozenset(("ORIGINAL_PC_ARCHIVE_LABEL_FROM_PHASE424",
                    "ORIGINAL_ANDROID_NOX_ARCHIVE_LABEL_FROM_PHASE424"))

def classify(data:object)->dict:
    base={
        "classification":"ARCHIVE_METADATA_INVALID",
        "original_client_order_observed":False,
        "emitting_app_cryptographically_verified":False,
        "official_account_authenticated_by_this_report":False,
        "independent_cgm_mobile_session_proven":False,
        "garden_authorized_for_new_mobile_session":False,
    }
    if not isinstance(data,dict) or set(data)!=ALLOWED:
        return base
    if (data["archive_provenance"] not in LABELS
        or data["historical_recording"] is not True
        or any(data[name] is not False for name in (
            "apk_process_identity_proven","official_provider_account_bound",
            "new_cgm_mobile_session_issued","cgmonly_vip_accepted"))
        or data["single_client_network_address"] is not True
        or data["single_tcp_connection_per_service"] is not True
        or type(data["tcp_sequence_gaps"]) is not int
        or data["tcp_sequence_gaps"]!=0
        or type(data["unconsumed_stream_bytes"]) is not int
        or data["unconsumed_stream_bytes"]!=0
        or type(data["stale_one_byte_overlap_candidates"]) is not int
        or data["stale_one_byte_overlap_candidates"]<0):
        return base
    frames=data["frames"]
    if (not isinstance(frames,dict) or set(frames)!=set(PORT_DIRECTIONS)
        or any(type(frames[key]) is not int or frames[key]<=0 for key in PORT_DIRECTIONS)
        or type(data["total_frames"]) is not int
        or sum(frames.values())!=data["total_frames"]):
        return base
    times=data["checkpoint_relative_seconds"]
    if not isinstance(times,dict) or set(times)!=set(STEPS):
        return base
    values=[times[k] for k in STEPS]
    if (any(type(v) not in (int,float) or v<0 or v>172800 for v in values)
        or values[0]!=0
        or any(b<=a for a,b in zip(values,values[1:]))):
        return base
    return base | {
        "classification":"HISTORICAL_ORDERED_ORIGINAL_CLIENT_RESPONSE_SHAPES",
        "original_client_order_observed":True,
        # No runtime process attribution or verified session issuer provided.
        "emitting_app_cryptographically_verified":False,
        "official_account_authenticated_by_this_report":False,
        "independent_cgm_mobile_session_proven":False,
        "garden_authorized_for_new_mobile_session":False,
    }

def main()->int:
    import argparse
    p=argparse.ArgumentParser(description=__doc__)
    p.add_argument("metadata",type=Path,nargs="+")
    args=p.parse_args()
    for path in args.metadata:
        record=json.loads(path.read_text(encoding="utf-8"))
        print(json.dumps(classify(record),sort_keys=True))
    return 0

if __name__=="__main__":raise SystemExit(main())
