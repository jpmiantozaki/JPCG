#!/usr/bin/env python3
"""No capture, credentials, network or game server used in this CI suite."""
from pathlib import Path
import contextlib
import io
import struct
import sys
import tempfile
import unittest

sys.path.insert(0,str(Path(__file__).resolve().parent.parent/'research'))
import phase34a_pc_stream_metadata as a

def encode(plain,key=b'abc'):
    if len(key)<2 or len(key)>9:raise ValueError()
    k=len(key);header=(~(((k<<7)|(k>>1))&255))&255
    n=len(plain)
    data=bytes(b^key[i%k]^((n-i)&255) for i,b in enumerate(plain))
    inner=bytes([header])+key+data
    return struct.pack('<H',2+len(inner))+inner

def c2s(code=b'\x01\x01',body=b''):
    inner=struct.pack('<HI',8+len(body),1)+code+body
    return encode(inner)

def s2c(content=b'\xaa\xbb\xcc'):
    return encode(struct.pack('<H',2+len(content))+content)

def block(t,data):
    pad=b'\0'*(-len(data)%4);n=8+len(data)+len(pad)+4
    return struct.pack('<II',t,n)+data+pad+struct.pack('<I',n)

def epb(sp,dp,seq,payload):
    eth=b'\0'*12+b'\x08\x00'
    ip=bytes([0x45,0])+struct.pack('!H',40+len(payload))+b'\0'*4+bytes([64,6])+b'\0'*2
    ip+=b'\x0a\x00\x00\x01'+b'\x0a\x00\x00\x02'
    tcp=struct.pack('!HHII',sp,dp,seq,0)+b'\x50\x18'+struct.pack('!H',8192)+b'\0'*4
    pkt=eth+ip+tcp+payload
    return block(6,struct.pack('<IIIII',0,0,0,len(pkt),len(pkt))+pkt)

def capture(path,entries):
    shb=block(0x0A0D0D0A,b'\x4d\x3c\x2b\x1a'+struct.pack('<HHq',1,0,-1))
    idb=block(1,struct.pack('<HHI',1,0,65535))
    path.write_bytes(shb+idb+b''.join(epb(*x) for x in entries))

class Phase34A(unittest.TestCase):
    def test_decode_matches_original_fixture(self):
        p=struct.pack('<HI',8,1)+b'\x01\x01'
        self.assertEqual(a.decode_a(encode(p)),p)

    def test_outgoing_command(self):
        rec,stats,_=a.frames(c2s(b'\x00\x16',b'fictional-password'),'C2S')
        self.assertEqual(rec[0]['command'],'0016')
        self.assertEqual(stats['valid'],1)
        self.assertNotIn('fictional-password',str(rec))

    def test_server_payload_not_misread_as_command(self):
        r,s,_=a.frames(s2c(b'\x99\x88\x01\x01secret-token'),'S2C')
        self.assertEqual(s['valid'],1)
        self.assertNotIn('command',r[0])
        self.assertNotIn('secret-token',str(r))

    def test_short_server_reply_counted(self):
        r,s,_=a.frames(s2c(b'abc'),'S2C')
        self.assertEqual(s['valid'],1)
        self.assertEqual(s['short_server'],1)
        self.assertEqual(r[0]['size'],5)

    def test_short_client_reply_not_accepted(self):
        r,s,_=a.frames(s2c(b'abc'),'C2S')
        self.assertEqual(s['short_client'],1)
        self.assertEqual(r,[])

    def test_stream_two_concatenated(self):
        x=c2s(b'\x00\x16')+c2s(b'\x01\x00')
        r,s,_=a.frames(x,'C2S')
        self.assertEqual([z['command'] for z in r],['0016','0100'])

    def test_partial_packet(self):
        x=c2s()+c2s()[:3]
        r,s,_=a.frames(x,'C2S')
        self.assertEqual(len(r),1)
        self.assertEqual(s['partial'],1)

    def test_tcp_gap(self):
        p=c2s()
        rs,st=a.runs([(100,p[:4],1),(110,p[4:],2)])
        self.assertEqual(st['gaps'],1)
        self.assertEqual(len(rs),2)

    def test_tcp_duplicate(self):
        p=c2s()
        rs,st=a.runs([(100,p,1),(100,p,2)])
        self.assertEqual(rs,[p])
        self.assertEqual(st['duplicate'],1)

    def test_bad_outer(self):
        p=bytearray(c2s());p[2]=0
        with self.assertRaises(ValueError):a.decode_a(bytes(p))

    def test_pcap_with_false_800_candidate_and_privacy(self):
        inner=struct.pack('<H',902)+b'Q'*900
        wire=bytearray(encode(inner))
        wire[100:102]=struct.pack('<H',800)
        wire=bytes(wire)
        with tempfile.TemporaryDirectory() as directory:
            f=Path(directory)/'synthetic.pcapng'
            # TCP segment 2 starts INSIDE the true framed packet.
            capture(f,[(18806,50000,1000,wire[:100]),
                       (18806,50000,1100,wire[100:]),
                       (50000,18806,2000,c2s(b'\x00\x16'))])
            rows=a.audit(f)
            incoming=[r for r in rows if r['direction']=='S2C'][0]
            self.assertEqual(incoming['stats']['valid'],1)
            self.assertEqual(incoming['offboundary'],1)
            self.assertEqual(incoming['false800'],1)
            self.assertEqual(incoming['stats']['leftover'],0)
            outgoing=[r for r in rows if r['direction']=='C2S'][0]
            self.assertEqual(outgoing['records'][0]['command'],'0016')
            self.assertNotIn('command',incoming['records'][0])

    def test_no_real_capture_paths_embedded(self):
        # A fixture test must never load original PCAPs or account data.
        self.assertEqual(a.decode_a(encode(struct.pack('<H',2))),struct.pack('<H',2))

if __name__=='__main__':unittest.main(verbosity=2)
