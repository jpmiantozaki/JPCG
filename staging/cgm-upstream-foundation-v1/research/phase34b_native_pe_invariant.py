#!/usr/bin/env python3
"""Hash-pinned, read-only x86 PC native dispatcher registration check.

No binary code, packet payload or account material is included in reports.
The original executable must be separately supplied by its owner.
"""
from __future__ import annotations
from pathlib import Path
import argparse
import hashlib
import struct

PE_SHA256 = 'a2d0cf35814184d984f42957da79f8aa4241181c63f3e258917cf709cee5b911'


def va_offset(va: int) -> int:
    sections = [(0x401000, 0x7c6946, 0x400),
                (0xbc8000, 0x46240e, 0x7c6e00),
                (0x102b000, 0x1f49f34, 0xc29400)]
    for base, size, fileoff in sections:
        if base <= va < base + size:
            return fileoff + (va - base)
    raise ValueError('address outside original executable sections')


# Version-pinned instruction assertions. No original binary bundled.
CHECKS = {
    0x009DF0B0: bytes.fromhex('89 04 8d f0 fa ed 02'),
    0x009DFBC2: bytes.fromhex('e8 f9 13 00 00'),
    0x009DFBCD: bytes.fromhex('8b 88 02 c8 00 00'),
    0x009DFBD3: bytes.fromhex('8a 11'),
    0x009DFC04: bytes.fromhex('8b 0c 8d f0 fa ed 02'),
    0x009E0FC0: bytes.fromhex('b8 fe ff 00 00 66 01 01 8d 41 02'),
    0x00408A22: bytes.fromhex('6a 01'),
    0x00408A32: bytes.fromhex('e8 69 66 5d 00'),
    0x00408A40: bytes.fromhex('c7 05 28 3f c5 01 c8 71 fd 00'),
    0x00A1F6EC: bytes.fromhex('8a 39'),
    0x00A1F6F8: bytes.fromhex('8a 19'),
    0x00A1F71D: bytes.fromhex('84 ff'),
    0x00A1F725: bytes.fromhex('84 db'),
    0x00A1FF2E: bytes.fromhex('80 ff 01'),
    0x00A1FF37: bytes.fromhex('84 db'),
    0x004096B2: bytes.fromhex('6a 00'),
    0x00407BB2: bytes.fromhex('6a 30'),
}
VTABLE_CASES = {
    0x01: (0x00FD71C8, 0x00A1F6D0),
    0x00: (0x00FCD404, 0x009E3640),
    0x30: (0x00FD7334, 0x00A1A490),
}


def inspect(executable: Path) -> dict:
    data = executable.read_bytes()
    if hashlib.sha256(data).hexdigest() != PE_SHA256:
        raise ValueError('exact original PC executable hash required')
    if len(data) != 46613504:
        raise ValueError('unrecognized original PE size')
    for va, expected in CHECKS.items():
        off = va_offset(va)
        if data[off:off+len(expected)] != expected:
            raise ValueError('native dispatcher instruction mismatch')
    for opcode, (vtable, expected_handler) in VTABLE_CASES.items():
        target = struct.unpack_from('<I', data, va_offset(vtable+4))[0]
        if target != expected_handler:
            raise ValueError('native handler vtable mismatch')
    return {
        'result': 'ORIGINAL_PC_NATIVE_DISPATCH_INVARIANTS_PASS',
        'instruction_checks': len(CHECKS),
        'vtable_handler_checks': len(VTABLE_CASES),
        'registered_handler_00': '009E3640',
        'registered_handler_01': '00A1F6D0',
        'registered_handler_30': '00A1A490',
        'incoming_dispatch': 'DECODED_FRAME_OFFSET_2',
        'opcode01_subtype': 'DECODED_FRAME_OFFSET_3',
        'opcode01_result': 'DECODED_FRAME_OFFSET_4',
        'official_mobile_game_session_proven': False,
    }


def main(argv=None):
    p=argparse.ArgumentParser(description=__doc__)
    p.add_argument('original_exe',type=Path)
    args=p.parse_args(argv)
    for key,value in inspect(args.original_exe).items():
        print('%s=%s' % (key,value))
    return 0


if __name__=='__main__':
    raise SystemExit(main())
