#!/usr/bin/env python3
"""Phase34A: read-only 18805/18806 TCP stream audit.

Never log original packet bytes, IP addresses, credentials, relay tokens,
account identifiers, server response payload fields or session data.
This file does not connect to any server or perform authentication.
"""
from __future__ import annotations
import argparse
from collections import Counter, defaultdict
from pathlib import Path
import struct

def decode_a(outer: bytes) -> bytes:
    if len(outer)<6 or struct.unpack_from('<H',outer)[0]!=len(outer):
        raise ValueError('outer length')
    enc=outer[2:]
    x=(~enc[0])&255
    k=((x>>7)|(x<<1))&255
    if not 2<=k<=9 or len(enc)<1+k: raise ValueError('key length')
    key=enc[1:1+k];data=enc[1+k:];n=len(data)
    inner=bytes(b^key[i%k]^((n-i)&255) for i,b in enumerate(data))
    if len(inner)<2 or struct.unpack_from('<H',inner)[0]!=len(inner):
        raise ValueError('inner length')
    return inner

def packets(path:Path):
    ifaces=[]
    with path.open('rb') as f:
        idx=0
        while True:
            head=f.read(8)
            if not head:break
            if len(head)!=8:raise ValueError('truncated PCAPNG header')
            kind,total=struct.unpack('<II',head)
            if total<12 or total>33554432:raise ValueError('PCAPNG block length')
            b=f.read(total-8)
            if len(b)!=total-8 or struct.unpack_from('<I',b,len(b)-4)[0]!=total:
                raise ValueError('PCAPNG block integrity')
            if kind==0x0A0D0D0A:
                if b[:4]!=bytes.fromhex('4d3c2b1a'):raise ValueError('endian unsupported')
                ifaces=[]
            elif kind==1:ifaces.append(struct.unpack_from('<H',b)[0])
            elif kind==6:
                idx+=1
                if len(b)<24:continue
                ifid,_,_,caplen,_=struct.unpack_from('<IIIII',b)
                if ifid>=len(ifaces) or ifaces[ifid]!=1:continue
                eth=b[20:20+caplen]
                if len(eth)<54:continue
                etype=struct.unpack_from('!H',eth,12)[0];off=14
                if etype==0x8100 and len(eth)>=18:
                    etype=struct.unpack_from('!H',eth,16)[0];off=18
                if etype!=0x0800:continue
                ip=eth[off:]
                if len(ip)<20 or ip[9]!=6:continue
                ihl=(ip[0]&15)*4
                if ihl<20 or len(ip)<ihl+20:continue
                iplen=struct.unpack_from('!H',ip,2)[0]
                tcp=ip[ihl:min(len(ip),iplen)]
                if len(tcp)<20:continue
                sp,dp=struct.unpack_from('!HH',tcp)
                if sp not in (18805,18806) and dp not in (18805,18806):continue
                thl=(tcp[12]>>4)*4
                if thl<20 or thl>len(tcp):continue
                payload=tcp[thl:]
                if not payload:continue
                port=sp if sp in (18805,18806) else dp
                direction='S2C' if sp in (18805,18806) else 'C2S'
                seq=struct.unpack_from('!I',tcp,4)[0]
                # Endpoint bytes are only a private in-memory grouping key.
                key=(ip[12:16],sp,ip[16:20],dp)
                yield key,port,direction,seq,payload,idx

def runs(parts):
    if not parts:return [],Counter()
    out=[];cur=bytearray();end=None;stats=Counter()
    for seq,data,idx in sorted(parts,key=lambda t:(t[0],t[2])):
        if end is None:
            cur.extend(data);end=seq+len(data)
        elif seq>end:
            out.append(bytes(cur));cur=bytearray(data);end=seq+len(data)
            stats['gaps']+=1
        elif seq+len(data)<=end:
            stats['duplicate']+=1
        else:
            old=end-seq
            if old:stats['overlap']+=1
            cur.extend(data[old:]);end=seq+len(data)
    if cur:out.append(bytes(cur))
    return out,stats

def frames(data:bytes,direction:str):
    off=0;records=[];stats=Counter();starts=set()
    while off+2<=len(data):
        size=struct.unpack_from('<H',data,off)[0]
        if size<6 or size>65535:
            stats['bad_header']+=1;break
        if off+size>len(data):
            stats['partial']+=1;break
        starts.add(off)
        try:
            inner=decode_a(data[off:off+size])
        except ValueError:
            stats['decode_error']+=1;off+=size;continue
        record={'size':len(inner)}
        # C2S alone has proven [u16][u32 counter][u16 opcode] header.
        # Do NOT infer a server opcode from inbound bytes 6..7.
        if direction=='C2S':
            if len(inner)<8:stats['short_client']+=1;off+=size;continue
            record['command']=inner[6:8].hex().upper()
        elif len(inner)<8:
            stats['short_server']+=1
        records.append(record);stats['valid']+=1;off+=size
    stats['leftover']=len(data)-off
    return records,stats,starts

def audit(path:Path):
    groups=defaultdict(list)
    for key,port,d,seq,body,idx in packets(path):
        groups[(key,port,d)].append((seq,body,idx))
    result=[]
    for (_,port,d),parts in groups.items():
        rs,tcp=runs(parts);rec=[];stats=Counter()
        for data in rs:
            r,s,_=frames(data,d)
            rec+=r;stats.update(s)
        # Re-check questionable segment-only LE16 boundaries, only for
        # complete no-gap runs: original 800-byte anomaly is off-boundary.
        offbound=0;false800=0
        if len(rs)==1 and not tcp['gaps'] and not stats['leftover']:
            _,_,starts=frames(rs[0],d)
            base=min(seq for seq,body,i in parts)
            for seq,data,i in parts:
                k=0
                while k+2<=len(data):
                    n=struct.unpack_from('<H',data,k)[0]
                    if n<6 or n>len(data)-k:break
                    if seq-base+k not in starts:
                        offbound+=1
                        if n==800:false800+=1
                    k+=n
        result.append({'port':port,'direction':d,'records':rec,'stats':stats,
                       'tcp':tcp,'offboundary':offbound,'false800':false800,
                       'first_idx':min(p[2] for p in parts)})
    result.sort(key=lambda x:x['first_idx'])
    return result

def main():
    cli=argparse.ArgumentParser(description=__doc__)
    cli.add_argument('pcapng',type=Path,nargs='+')
    args=cli.parse_args()
    for path in args.pcapng:
        rows=audit(path)
        print('CAPTURE=%s UNIQUE_FRAMES=%d'%(path.name,sum(len(x['records']) for x in rows)))
        for r in rows:
            print(' port=%d direction=%s decoded=%d short_server=%d gaps=%d bad=%d leftovers=%d offboundary_segment_candidates=%d false_800=%d'%
                  (r['port'],r['direction'],r['stats']['valid'],r['stats']['short_server'],
                   r['tcp']['gaps'],r['stats']['bad_header']+r['stats']['decode_error'],
                   r['stats']['leftover'],r['offboundary'],r['false800']))
            if r['direction']=='C2S':
                print(' outgoing_commands_first_12=%s'%' '.join(v['command'] for v in r['records'][:12]))
    print('OFFICIAL_MOBILE_ACCEPTANCE=NOT_PROVEN; C2S_COMMAND_OFFSETS_ONLY; NO_ACCOUNT_OR_SESSION_DATA')

if __name__=='__main__':main()
