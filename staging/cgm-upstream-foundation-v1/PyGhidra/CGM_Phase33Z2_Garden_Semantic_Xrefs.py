#@runtime PyGhidra
#@category CGM
"""Original Audition Windows executable: read-only Garden semantic-anchor trace.
Targets proven UI strings and candidate C8 object field references rather than
repeating already-classified vector/tree/sort helpers. No live network, patching,
credentials, packet buffers, or game-server requests.
"""
import os
from ghidra.program.flatapi import FlatProgramAPI
from ghidra.app.decompiler import DecompInterface
from ghidra.util.task import TaskMonitor


def active_program():
    try:
        if currentProgram is not None:
            return currentProgram
    except NameError:
        pass
    try:
        active = state.getCurrentProgram()
        if active is not None:
            return active
    except (NameError, AttributeError):
        pass
    raise RuntimeError('Open Audition_unprotected_extracted.exe in Ghidra CodeBrowser')


p = active_program()
if ('audition_unprotected_extracted' not in str(p.getName()).lower() or
        int(p.getImageBase().getOffset()) != 0x00400000):
    raise RuntimeError('Wrong program or image base; expected original Windows Audition')
api = FlatProgramAPI(p)
mem = p.getMemory()
listing = p.getListing()
funcs = p.getFunctionManager()
refs = p.getReferenceManager()

def at(va):
    return api.toAddr(va)

def read_bytes(va, n):
    return bytes((int(mem.getByte(at(va+j))) & 255) for j in range(n))

# Prior validated Phase33Y C8 registration & subtype table pointer.
if read_bytes(0x00A6965D, 5) != bytes.fromhex('68c8000000'):
    raise RuntimeError('C8 registration differs from original executable')
if read_bytes(0x00A6AA75, 7) != bytes.fromhex('0fb6808800a700'):
    raise RuntimeError('C8 subtype dispatcher differs from original executable')

# Binary fingerprinted, static exact-address anchors. 'hits' are the locations
# of VA-sized numeric literals, NOT yet assertions of valid instructions or
# genuine semantic references; Ghidra resolves those below.
MANIFEST = {'anchors': {'CG_DIEFLOWER': {'va': 12414488, 'hits': [4887846, 9349642]}, 'CG_NOFLOWER': {'va': 12414436, 'hits': [4887734, 9349510]}, 'CoupleGardenAppraisal': {'va': 12418640, 'hits': [4962681, 4995500]}, 'CoupleGardenExpansion': {'va': 12418796, 'hits': [4951564, 4994948]}, 'CoupleGardenShop': {'va': 12422084, 'hits': [5030865, 5031748, 5032216, 5032596, 5033193, 5037204, 5038977, 5039430, 5039470, 5039514, 5039558, 5039626, 5039705, 5039737, 5039779, 5039831, 5040270, 5040323, 5040594, 5040771, 5040882, 5040925, 5043239, 5043768, 5043805, 5049686, 5049816, 5049853]}, 'CoupleGarden_1Page1Tree': {'va': 12415996, 'hits': [4896394]}, 'GARDEN_ADVANCE_ITEM_%d': {'va': 12413372, 'hits': [4913008]}, 'Garden_Error': {'va': 12419108, 'hits': [4983745, 4983892, 4989944, 4991614, 4993930, 4993982, 4994030, 4994078, 5013458, 5013487, 6905596, 10967797, 11004955]}, 'Garden_refill_success': {'va': 12415924, 'hits': [4895962]}, 'TEXTURE/gui/SecretGarden': {'va': 12413184, 'hits': [4882619, 4916526, 4931277, 8221966, 11260594, 11261153, 11261385, 11261866, 11263113, 11263165, 11263352, 11263554, 11263865, 11265042, 11265390, 11266202, 11267150, 11267321, 11269185, 11269313, 11269394, 11270811, 11271528, 11271655, 11272020, 11277268, 11278657, 11286385, 11287129, 11288109]}, 'cg_seed_UP_%d': {'va': 12419984, 'hits': [4967446]}, 'garden_garade_%d': {'va': 12419324, 'hits': [4961657, 9593408, 9593479, 9716928, 11272324]}}, 'fields': {'19C4': [10475747, 10717402, 10917568, 10922215, 10922779, 10922791], '19EC': [4331282, 10390021, 10919718, 10926752, 10932701, 10933251, 11972299], '19F4': [5302408, 10919728, 10926740, 10932692, 10933242], '1A08': [8049187, 8823056, 8823694, 8829352, 8829777, 8835492, 8837313, 8839261, 8839274, 8840604, 8862061, 8862534, 8937974, 8938074, 8959329, 8970969, 8977885, 8981960, 8982012, 8982058, 8982086, 9543737, 9543768, 10475432, 10494222, 10917715, 10918790, 10922158, 10922164, 10922815, 10925296, 10925423, 10925564, 11192711, 11192759, 11192903, 11192951, 11193231, 11193272, 11193420, 11193468, 12319444], '1B20': [7758413, 10395280, 10617131, 10708834, 10917991, 10922039, 10922844, 10924233, 10931851, 10984786, 10990627, 12037242, 12319594], '1B24': [10395268, 10463855, 10922855, 10924167, 10924216, 10984796], '1B38': [10396137, 10463886, 10617191, 10617227, 10918081, 10922018, 10926613, 10932902, 10984538, 10990313, 12319618], '1B3C': [10395025, 10617235, 10918894, 10925754], '1B60': [6062191, 6064228, 10394976, 10918137, 10921891, 10932956]}}

lines=[]
def log(msg=''):
    lines.append(str(msg))

def func_label(site):
    f=funcs.getFunctionContaining(at(site))
    return str(f.getName())+'@'+str(f.getEntryPoint()) if f else '(unresolved function)'

def instruction_label(site):
    ins=listing.getInstructionContaining(at(site))
    if ins is None:
        return '(no Ghidra instruction)'
    return str(ins.getAddress())+' '+str(ins)

log('CGM PHASE 3.3Z.2 -- Garden specific semantic/record consumer candidates')
log('program='+str(p.getName())+' imagebase=0x00400000 signatures=PASS')
log('Read only, no runtime, credentials, captured packets, or APK modifications')
log('Important: raw embedded-pointer or displacement matches need Ghidra validation')

score={}
for name, rec in MANIFEST['anchors'].items():
    va=rec['va']
    if read_bytes(va,len(name)) != name.encode('ascii'):
        raise RuntimeError('Expected Garden UI string was absent: '+name)
    candidates=set(rec['hits'])
    for r in refs.getReferencesTo(at(va)):
        candidates.add(int(r.getFromAddress().getOffset()))
    log('\nUI ANCHOR '+name+' @%08X, candidates=%d'%(va,len(candidates)))
    ordered=sorted(candidates)
    for loc in ordered[:24]:
        label=func_label(loc)
        log('  pointer_at=%08X %s | %s'%(loc,label,instruction_label(loc)))
        if '(unresolved' not in label:
            score[label]=score.get(label,0)+4
    if len(ordered)>24:
        log('  +%d further locations omitted'%(len(ordered)-24))

for name, hits in MANIFEST['fields'].items():
    log('\nC8 OBJECT FIELD DISPLACEMENT 0x%s, literal-sites=%d'%(name,len(hits)))
    owners={}
    for loc in hits:
        ins=listing.getInstructionContaining(at(loc))
        if ins is None:
            continue
        f=funcs.getFunctionContaining(at(loc))
        if f is None:
            continue
        key=str(f.getName())+'@'+str(f.getEntryPoint())
        owners.setdefault(key,[]).append((loc,ins))
        score[key]=score.get(key,0)+1
    for key,vals in sorted(owners.items(),key=lambda item:(-len(item[1]),item[0]))[:25]:
        a,ins=vals[0]
        log('  owner=%s hits=%d sample=%08X %s'%(key,len(vals),a,str(ins)))
    if len(owners)>25:
        log('  +%d further function owners omitted'%(len(owners)-25))

log('\nMOST REFERENCED CANDIDATES (BOTH UI ANCHORS AND OBJECT FIELDS ARE HEURISTICS):')
for key,value in sorted(score.items(),key=lambda v:(-v[1],v[0]))[:26]:
    log(' score=%02d %s'%(value,key))

# Decompile targeted function bodies in CodeBrowser, bounded output. These
# anchors were selected from the exact PE32, not from a generic guess.
FOCUS=[
    ('C8 related packet builder',0x00A710E0),
    ('Page 1 Garden UI component',0x004AB68A),
    ('Garden advanced item label producer',0x004AF770),
    ('Garden seed upgrade label producer',0x004BCC16),
    ('Garden no-flower message consumer',0x004A94B6),
    ('Object field 1B38 consumer candidate',0x00A79C5A),
    ('Object field 1B38 consumer candidate 2',0x00A20167),
]
D=DecompInterface()
if not D.openProgram(p):
    log('ERROR Ghidra decompiler unavailable')
else:
    seen=set()
    try:
        for title,loc in FOCUS:
            f=funcs.getFunctionContaining(at(loc))
            if f is None:
                log('\nDECOMPILE '+title+' @%08X: no function'%loc)
                continue
            addr=int(f.getEntryPoint().getOffset())
            if addr in seen:
                continue
            seen.add(addr)
            log('\n===== DECOMPILE '+title+' %s entry=%08X ====='%(str(f.getName()),addr))
            try:
                r=D.decompileFunction(f,35,TaskMonitor.DUMMY)
                if r is None or not r.decompileCompleted():
                    log('FAILED or timed out (not treated as a semantic result)')
                    continue
                c=str(r.getDecompiledFunction().getC())
                log(c[:8500])
                if len(c)>8500:
                    log('[decompilation excerpt truncated; full source remains in Ghidra]')
            except Exception as exc:
                log('Decompiler exception '+type(exc).__name__+': '+str(exc)[:180])
    finally:
        D.dispose()

log('\nVERDICT: C8/02 field semantics are NOT VERIFIED by raw xrefs alone.')
log('Next target: cross-reference a C8 record ID to a Garden resource/definition or contemporary UI observation.')

desktop=os.path.join(os.path.expanduser('~'),'Desktop')
if not os.path.isdir(desktop):
    desktop=os.path.expanduser('~')
report=os.path.join(desktop,'CGM_Phase33Z2_Garden_Semantic_Xrefs.txt')
with open(report,'w',encoding='utf-8') as out:
    out.write('\n'.join(lines)+'\n')
print('CGM_PHASE33Z2_GHIDRA_ANCHOR_TRACE_DONE: saved to '+report)
print('CGM_PHASE33Z2_NEXT: semantic mapping still requires UI/resource correlation')