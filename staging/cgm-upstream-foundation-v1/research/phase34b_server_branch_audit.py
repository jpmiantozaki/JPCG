#!/usr/bin/env python3
"""Phase34B: metadata-only original Windows PC native server response audit.

Offline archived PCAPNG only. No credentials, payloads, relay/session keys,
IPs, packet counters, or protected query data are returned or logged.
This does NOT authenticate an independent CGM mobile client.
"""
from __future__ import annotations
from collections import Counter, defaultdict
from pathlib import Path
from typing import Any
import struct

from phase34a_pc_stream_metadata import decode_a, packets, runs

SERVICE_PORTS = (18805, 18806)
OUTBOUND_NAMES = {
    '0101': 'LOGIN_SUBMISSION',
    '0016': 'VERIFY_CCU',
    '0100': 'GAME_LOGON',
    '0a00': 'USER_INFO_REQUEST',
    '0900': 'CHANNEL_OPERATION',
    '1f0c': 'COUPLE_REQUEST',
    '0000': 'PING',
}


def classify_inbound_frame(inner: bytes, port: int) -> str:
    """Classify only original PC receiver fields proven by native instructions.

    The common receiver 0x009DFAE0 invokes handler based on inner offset 2.
    For dispatch opcode 01, handler 0x00A1F6D0 reads offset 3 (subtype)
    and 4 (result), and tests two success-style conditions.
    """
    if port not in SERVICE_PORTS:
        return 'UNSUPPORTED_SERVICE'
    if len(inner) < 3 or struct.unpack_from('<H', inner)[0] != len(inner):
        return 'INVALID_FRAME_SHAPE'
    opcode = inner[2]
    if opcode != 1:
        if opcode in (0, 0x30):
            return 'KNOWN_DISPATCH_OPCODE_OTHER_HANDLER'
        return 'OTHER_NATIVE_DISPATCH_OPCODE_UNINTERPRETED'
    if len(inner) < 5:
        return 'OPCODE01_TOO_SHORT_FOR_NATIVE_RESULT'
    subtype, result = inner[3], inner[4]
    if port == 18805 and subtype == 1 and result == 0:
        return 'ORIGINAL_PC_LOGINSERVER_SUBTYPE1_ZERO_RESULT_BRANCH'
    if port == 18806 and subtype == 0 and result == 0:
        return 'ORIGINAL_PC_GAMESERVER_SUBTYPE0_ZERO_RESULT_BRANCH'
    return 'OPCODE01_OTHER_SUBTYPE_OR_RESULT'


def scan_reassembled_run(stream: bytes, port: int, direction: str):
    """Return only vetted branch labels or fixed outgoing opcode classes."""
    if direction not in ('S2C', 'C2S') or port not in SERVICE_PORTS:
        raise ValueError('unrecognized direction or service')
    stats = Counter()
    labels = []
    offset = 0
    while offset + 2 <= len(stream):
        length = struct.unpack_from('<H', stream, offset)[0]
        if not 6 <= length <= 65535:
            stats['invalid_outer_header'] += 1
            break
        if offset + length > len(stream):
            stats['truncated_outer_frame'] += 1
            break
        try:
            inner = decode_a(stream[offset:offset + length])
        except (ValueError, IndexError, struct.error):
            stats['undecodable_frame'] += 1
            offset += length
            continue
        if direction == 'S2C':
            label = classify_inbound_frame(inner, port)
        elif len(inner) < 8:
            label = 'CLIENT_FRAME_TOO_SHORT'
        else:
            label = OUTBOUND_NAMES.get(inner[6:8].hex(), 'OTHER_CLIENT_COMMAND_UNINTERPRETED')
        labels.append(label)
        stats['decoded'] += 1
        offset += length
    stats['unconsumed_bytes'] = len(stream) - offset
    return labels, stats


def inspect_capture(path: Path) -> dict[str, Any]:
    if not path.is_file() or path.stat().st_size > 64 * 1024 * 1024:
        raise ValueError('missing or oversized archived PCAPNG')
    groups = defaultdict(list)
    for flow, port, direction, seq, data, packetidx in packets(path):
        groups[(flow, port, direction)].append((seq, data, packetidx))
    incoming, outgoing = Counter(), Counter()
    integrity = Counter()
    for (_, port, direction), segments in groups.items():
        chunks, tstats = runs(segments)
        integrity['tcp_gaps'] += tstats.get('gaps', 0)
        for chunk in chunks:
            labels, stats = scan_reassembled_run(chunk, port, direction)
            integrity.update(stats)
            (incoming if direction == 'S2C' else outgoing).update(labels)
    evidence = {
        'recorded_pc_login_request': outgoing['LOGIN_SUBMISSION'] > 0,
        'recorded_pc_login_zero_result_branch':
            incoming['ORIGINAL_PC_LOGINSERVER_SUBTYPE1_ZERO_RESULT_BRANCH'] > 0,
        'recorded_pc_game_logon_request': outgoing['GAME_LOGON'] > 0,
        'recorded_pc_game_zero_result_branch':
            incoming['ORIGINAL_PC_GAMESERVER_SUBTYPE0_ZERO_RESULT_BRANCH'] > 0,
    }
    return {
        'provenance': 'HISTORICAL_USER_OWNED_PC_RECORDING_ONLY',
        'capture_compatible_with_native_pc_success_branches': all(evidence.values()),
        'evidence': evidence,
        'inbound_branch_counts': dict(sorted(incoming.items())),
        'outbound_command_counts': dict(sorted(outgoing.items())),
        'total_reassembled_frames': integrity['decoded'],
        'integrity': dict(sorted(integrity.items())),
        'attempt_generation_correlated': False,
        'same_official_account_proven': False,
        'independently_authenticated_cgm_mobile_session': False,
        'official_provider_attestation': False,
        'garden_authorization_for_new_mobile_session': False,
        'server_response_payloads_recorded_in_output': False,
    }


def main(argv=None):
    import argparse
    import json
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('captures', type=Path, nargs='+')
    args = parser.parse_args(argv)
    for index, path in enumerate(args.captures, 1):
        result = inspect_capture(path)
        # Omit original capture paths, IPs and any raw packet bytes.
        print('CAPTURE_%d %s' % (index, json.dumps(result, sort_keys=True)))
    print('PC_NATIVE_BRANCH_MATCH_NOT_CGM_MOBILE_AUTHENTICATION')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
