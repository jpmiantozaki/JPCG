#!/usr/bin/env python3
"""Synthetic-only Phase 34B regression tests; no provider, APK or PCAP."""
from pathlib import Path
import json
import struct
import sys
import tempfile
import unittest

sys.path.insert(0,str(Path(__file__).resolve().parent.parent/'research'))
import phase34b_server_branch_audit as a
import phase34b_native_pe_invariant as pe


def encoded_frame(plaintext):
    key=b'SYN';n=len(plaintext);k=len(key)
    h=(~(((k<<7)|(k>>1))&255))&255
    e=bytes([h])+key+bytes(b^key[i%k]^((n-i)&255) for i,b in enumerate(plaintext))
    return struct.pack('<H',len(e)+2)+e

def incoming(op,sub,result,secret=b''):
    data=bytes([op,sub,result])+secret
    return encoded_frame(struct.pack('<H',len(data)+2)+data)

def outgoing(command):
    data=struct.pack('<I',1)+bytes.fromhex(command)
    return encoded_frame(struct.pack('<H',len(data)+2)+data)

def block(kind,data):
    data+=b'\0'*(-len(data)%4)
    size=len(data)+12
    return struct.pack('<II',kind,size)+data+struct.pack('<I',size)

def pcap_pkt(source_port,dest_port,seq,data):
    eth=b'\0'*12+b'\x08\x00'
    ip=b'\x45\0'+struct.pack('!H',40+len(data))+b'\0'*4+b'\x40\x06'+b'\0'*2
    ip+=b'\x0a\0\0\x01'+b'\x0a\0\0\x02'
    tcp=struct.pack('!HHII',source_port,dest_port,seq,0)+b'\x50\x18'
    tcp+=struct.pack('!H',4096)+b'\0'*4
    pkt=eth+ip+tcp+data
    return block(6,struct.pack('<IIIII',0,0,0,len(pkt),len(pkt))+pkt)

def pcap(path,parts):
    data=block(0x0A0D0D0A,b'\x4d\x3c\x2b\x1a'+struct.pack('<HHq',1,0,-1))
    data+=block(1,struct.pack('<HHI',1,0,65535))
    data+=b''.join(pcap_pkt(*v) for v in parts)
    path.write_bytes(data)

class BranchTests(unittest.TestCase):
    def test_login_server_success_branch(self):
        b=struct.pack('<H',5)+b'\x01\x01\x00'
        self.assertIn('LOGINSERVER_SUBTYPE1',a.classify_inbound_frame(b,18805))

    def test_game_server_success_branch(self):
        b=struct.pack('<H',5)+b'\x01\x00\x00'
        self.assertIn('GAMESERVER_SUBTYPE0',a.classify_inbound_frame(b,18806))

    def test_rejected_login(self):
        b=struct.pack('<H',5)+b'\x01\x01\x03'
        self.assertEqual(a.classify_inbound_frame(b,18805),'OPCODE01_OTHER_SUBTYPE_OR_RESULT')

    def test_rejected_game(self):
        b=struct.pack('<H',5)+b'\x01\x00\x01'
        self.assertEqual(a.classify_inbound_frame(b,18806),'OPCODE01_OTHER_SUBTYPE_OR_RESULT')

    def test_wrong_port(self):
        b=struct.pack('<H',5)+b'\x01\x00\x00'
        self.assertEqual(a.classify_inbound_frame(b,18805),'OPCODE01_OTHER_SUBTYPE_OR_RESULT')

    def test_other_registered_handler(self):
        for i in (0,0x30):
            b=struct.pack('<H',5)+bytes([i,0,0])
            self.assertEqual(a.classify_inbound_frame(b,18806),'KNOWN_DISPATCH_OPCODE_OTHER_HANDLER')

    def test_other_opcode_does_not_disclose_code(self):
        b=struct.pack('<H',5)+b'\xfe\x00\x00'
        self.assertEqual(a.classify_inbound_frame(b,18806),'OTHER_NATIVE_DISPATCH_OPCODE_UNINTERPRETED')

    def test_short_and_invalid_frames(self):
        self.assertEqual(a.classify_inbound_frame(b'\x03\x00\x01',18806),'OPCODE01_TOO_SHORT_FOR_NATIVE_RESULT')
        self.assertEqual(a.classify_inbound_frame(b'\x09\x00\x01\x01\x00',18805),'INVALID_FRAME_SHAPE')

    def test_safe_pc_response_metadata_only(self):
        labels,stats=a.scan_reassembled_run(incoming(1,1,0,b'FAKE_PASSWORD'),18805,'S2C')
        self.assertEqual(labels,['ORIGINAL_PC_LOGINSERVER_SUBTYPE1_ZERO_RESULT_BRANCH'])
        self.assertEqual(stats['decoded'],1)
        self.assertNotIn('FAKE_PASSWORD',str(labels))

    def test_outgoing_opcode_mapping(self):
        labels,stats=a.scan_reassembled_run(outgoing('0016')+outgoing('0100'),18806,'C2S')
        self.assertEqual(labels,['VERIFY_CCU','GAME_LOGON'])
        self.assertEqual(stats['unconsumed_bytes'],0)

    def test_truncated_packet_not_counted(self):
        q=incoming(1,0,0)
        labels,stats=a.scan_reassembled_run(q[:3],18806,'S2C')
        self.assertEqual(labels,[])
        self.assertGreater(stats['unconsumed_bytes'],0)

    def test_no_mobile_authorization_in_capture_claim(self):
        with tempfile.TemporaryDirectory() as d:
            file=Path(d)/'fictional.pcapng'
            pcap(file,[
                (50000,18805,100,outgoing('0101')),
                (18805,50000,400,incoming(1,1,0)),
                (50001,18806,800,outgoing('0100')),
                (18806,50001,1200,incoming(1,0,0)),
            ])
            r=a.inspect_capture(file)
            self.assertTrue(r['capture_compatible_with_native_pc_success_branches'])
            for k in ('independently_authenticated_cgm_mobile_session',
                      'official_provider_attestation',
                      'garden_authorization_for_new_mobile_session',
                      'same_official_account_proven','attempt_generation_correlated'):
                self.assertFalse(r[k])
            self.assertEqual(r['total_reassembled_frames'],4)
            self.assertNotIn('50000',json.dumps(r))

    def test_failure_result_not_accepted(self):
        with tempfile.TemporaryDirectory() as d:
            file=Path(d)/'f.pcapng'
            pcap(file,[
                (50000,18805,100,outgoing('0101')),
                (18805,50000,400,incoming(1,1,2)),
                (50001,18806,800,outgoing('0100')),
                (18806,50001,1200,incoming(1,0,3)),
            ])
            self.assertFalse(a.inspect_capture(file)['capture_compatible_with_native_pc_success_branches'])

    def test_binary_hash_rejection(self):
        with tempfile.TemporaryDirectory() as d:
            path=Path(d)/'not-original.exe';path.write_bytes(b'fictional')
            with self.assertRaises(ValueError):pe.inspect(path)

    def test_native_pe_offset_calculation(self):
        self.assertEqual(pe.va_offset(0x9dfbd3),0x400+(0x9dfbd3-0x401000))

if __name__=='__main__':
    unittest.main(verbosity=2)
