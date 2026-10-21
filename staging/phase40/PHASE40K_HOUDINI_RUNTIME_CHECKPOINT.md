# Phase 40K — separate Nox 62032 native-runtime checkpoint

Date: 2026-10-21. User-provided Nox screenshot and read-only ADB output.

## Actual evidence

- New Nox `127.0.0.1:62032`, Android 9 / API 28; kernel reports `x86_64`; advertised ABI list includes `arm64-v8a`.
- Test APK: current known SHA-256 `44b89aa390734b2d05705a20620d6175e298ed790d88734d245e87d98ac00944`. Installed successfully; Android package manager reports `primaryCpuAbi=arm64-v8a`.
- CGM app process PID at observation `4173`; screenshot shows original yellow Login screen. No account input or credential-submit claimed.
- `/proc/4173/maps` reported app's ARM64 `libil2cpp.so` at base `0x0516c000`, and ARM64 `libunity.so`. Host executable `/system/vendor/lib64/libhoudini.so` is mapped. This proves a running translated Unity app, **not a verified hookable ARM64 entrypoint**.
- Phase 25V on different emulator/process used candidate code base `0x053b0000`. Neither absolute base is suitable for hardcoding into subsequent tests.
- The app ARM64 ELF map appears `r--p` for the displayed segments while `libhoudini.so` has executable host mappings. This warrants caution with inline code patching / cache behavior.

## Engineering decision

Phase 40J registration identity is settled and must not be re-scanned. Phase 25V reflection-based code pointer ownership is blocked because this runtime's enumerated RuntimeMethodHandle has no GetFunctionPointer method.

**Only next isolated proof:** a fresh fixture APK in a *distinct package*, no network permission, containing only its own ARM64 function, tested on disposable Nox 62032. It warms a function returning 7, temporarily branches to another fixture function returning 11, attempts instruction-cache synchronization, restores its own function bytes/protections, and checks it returns 7 again.

The fixture files `Phase40K-Synthetic-Houdini-Fixture.zip` were generated offline for local Windows build with the user's installed Android SDK/NDK. They are **unbuilt and not runtime-validated as of this note**. The script's build stage has no ADB actions; the install stage targets explicit serial 62032 and test package `com.cgm.phase40k.fixture` only. If Android toolchain or OS denies memory permission, keep that as evidence rather than loosening protections.

### Limits

A successful synthetic test proves only reversible branching of the fixture's warmed translated code. It does **not** justify modifying CGM Login/SendLogin, establish an original-call trampoline, authenticate accounts, enforce CGM ACTIVE, or make Garden sessions valid. Don't touch rollback 62030, old experimental 62031, official packets, or production servers.

## Executed Phase 40K fixture result (2026-10-21, user-provided Nox log)

- Compiled and v2/v3-signed dedicated APK SHA-256: `E974F063E1E51B4F29CEA18E949C088A8CAE137353AFF1693CC2E33BEA1D061F`.
- Installed exclusively on `127.0.0.1:62032` as `com.cgm.phase40k.fixture`; its process logged with PID `5666`.
- Observed: `PATCHED_GUEST_BRANCH_OBSERVED`, baseline `7`, warmed `7`, redirected return `11`, `hook calls: 1`, restored return `7`.
- Fixture log: `Protection before: 1; page size: 4096`; `Modified: yes; restored: yes; errno: 0; PASS: YES`.
- Native fixture only. No original CGM code, credentials, PlayPark packets or Garden state modified.
- This closes **single warmed synthetic function reversible branch feasibility** on this Houdini instance. It does **not** validate an executable detour on an original file-backed Unity function, trampoline relocation, exception handling, concurrent callers, thread quiescence, or auth interception.

## Phase 40L candidate

Implement a distinct **fixture-only** pass-through test: exercise a safe trampoline preserving original side effects and return value, repeated warm/call/unhook cycles; verify all active threads and restoration, and fail-closed on unexpected page or ABI state. No CGM login or production network. Only after isolated proof evaluate whether a supported integration route exists in the compiled APK, with explicit callback coverage and session dependency addressed separately.

## Phase 40L — isolated ARM64 pass-through experiment PASSED (user-reported, 2026-10-21)

- New fixture package `com.cgm.phase40l.fixture`, installed **only** to `127.0.0.1:62032`; CGM and earlier instances unchanged. Local signed APK SHA-256 `00FC6382FC150878D71676B0F8D9FC4613873D6BF0D04A6E79E19C7D96A7EBCC`. Signature verified with APK v2/v3.
- Process log PID `6051` tag `CGM40LFixture`: `PASSTHROUGH_TRAMPOLINE_VERIFIED`; initial/warmed `7/7`; **3 of 3** cycles, **24** pass-through calls, **24** hook calls; original counter **1,028**; restored bytes `yes`, target still modified `no`; protection before `1`, page `4096`, errno `0`; `PASS: YES`.
- The 1,028 original calls reconcile with fixture source: 1 baseline + 1,000 warmups + 24 hook-to-original continuations + 3 post-restore checks. No unexplained extra original calls.
- Source intentionally overwrites **one NOP** at synthetic `fixture_target()`. Its trampoline copies the NOP and executes a literal-load/indirect branch to `target+4`; hook increments its own counter, then calls trampoline; cleanup restores target bytes. This demonstrates *that exact simple original continuation shape*, not relocation of arbitrary instructions, actual Unity/IL2CPP method prologues, multi-threading, or executable page patching inside the installed CGM app.
- No CGM game code, official authentication, upstream packets, credential fields, session access or Garden state touched. This establishes only local guest translated fixture behavior, not a deployable Auth-First APK.

**Next engineering gate:** First identify a controlled, *non-authentication* original Unity callback that can be observed safely without altering game behavior; demonstrate ownership/thread/ABI and restoration strategy before considering interception. There is no justification to patch live `Login()` or `SendLogin()` based only on 40K/40L. A compliant source-level authorization gate remains preferable whenever integration artifacts are available.

## Phase 40M — original CGM non-auth read-only method anchor (prepared, not executed)

The next concrete phase is **not yet another synthetic APK**. It anchors the already-mapped `LoginServerManager.ShowLoginButton(bool)` UI-only method at native ARM64 RVA `0x6A5F20` in the real CGM process on Nox `127.0.0.1:62032`, without modifying or invoking any native game method. This method controls visibility/display state rather than credential submission. It is not `Login()`, `SendLogin()` or `ConnectToServer()`.

Source image: original `libil2cpp.so` SHA-256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8` shared across relevant CGM native builds. File bytes at RVA 0x6A5F20 are `f60f1df8f55301a9f37b02a9969900d0`, **real instructions, not the one-NOP fixture**. Any future trampoline needs instruction-specific relocation/ABI and concurrency proof; the fixture trampoline must not be transplanted.

Prepared artifact: `Phase40M-ReadOnly-CGM-Anchor.zip` with `Run-Only-62032.ps1`. Its guarded script checks the exact original CGM APK hash `44b89aa390734b2d05705a20620d6175e298ed790d88734d245e87d98ac00944`, validates native SHA and prologue offline, then reads only `get-state`, `pidof`, and `/proc/<pid>/maps` for `62032`. It derives a candidate **guest image address** relative to that process's mapped ELF base and emits a local JSON report. No runtime test result is claimed until user returns output. This is an anchor/mapping checkpoint, **not observed execution and not an inline hook**. All production and rollback environments untouched.

## Phase 40M — original CGM read-only anchor passed (user-run, 2026-10-21)

User ran `Phase40M-ReadOnly-CGM-Anchor/Run-Only-62032.ps1` on disposable Nox `127.0.0.1:62032`. The first attempt stopped safely because CGM was not running; the second attempt passed after opening the original yellow login screen, without pressing Login or entering credentials.

Observed console:
- Exact Phase40J CGM APK SHA-256: PASS
- Original ARM64 `libil2cpp.so` SHA-256: PASS
- Non-authentication `LoginServerManager.ShowLoginButton(bool)` static native RVA `0x6A5F20`
- Original 16-byte method prologue `f60f1df8f55301a9f37b02a9969900d0`
- Live process PID `6473`; mapped guest ELF base `0x0516C000`, **candidate guest** address `0x05811F20`; candidate page flags `r--p`
- Houdini module present: True
- `PASS: ORIGINAL CGM NON-AUTH METHOD MAPPED READ-ONLY`
- Local report: `D:\CGMTEST\Phase40M-ReadOnly-CGM-Anchor\results\phase40m-anchor.json`
- No live code modification, login function invocation, APK install, original credential packet, or backend network request.

No proof of native execution or host-executable address ownership is claimed. Next: offline disassembly of the exact original non-auth UI method at RVA `0x6A5F20`; characterize the first instructions and their PC-/stack-relative effects. Do **not** borrow the single-NOP fixture trampoline for original Unity code.

## Phase 40N — verified original UI method disassembly (user console, 2026-10-21)

User ran offline llvm-objdump from verified APK / extracted ARM64 lib (no Nox access), using `--start-address=0x6A5F20 --stop-address=0x6A5F60 --no-show-raw-insn`. Exact output:

```asm
6a5f20: str  x22, [sp, #-0x30]!
6a5f24: stp  x21, x20, [sp, #0x10]
6a5f28: stp  x19, x30, [sp, #0x20]
6a5f2c: adrp x22, 0x19d7000
6a5f30: adrp x21, 0x18d1000
6a5f34: ldrb w8, [x22, #0xa4e]
6a5f38: ldr  x21, [x21, #0x4b0]
6a5f3c: mov  w19, w1
6a5f40: mov  x20, x0
6a5f44: tbnz w8, #0x0, 0x6a5f5c
6a5f48: adrp x0, 0x18d1000
6a5f4c: ldr  x0, [x0, #0x4b0]
6a5f50: bl   0x4c81ac
6a5f54: mov  w8, #0x1
6a5f58: strb w8, [x22, #0xa4e]
6a5f5c: ldr  x0, [x21]
```

Interpretation: first 3 instructions save ABI registers and stack, first 16-byte overwrite window includes a PC-relative `ADRP` at +12. Later code has another `ADRP` and conditional/direct relative control flow. Hence Phase40L 1-NOP trampoline is not transplantable. A trampoline must preserve machine-state and retarget relocated `ADRP` for its original page and deal with instruction cache/Houdini and concurrent calls. Evidence is **static only**, not runtime detour safety, and says nothing about authorization/session validity.

Next bounded offline investigation: disassemble from `0x6A5F20` up to but not including the mapped next method `ShowSpinner(bool)` at `0x6A5FB8`, checking branches and epilogue. Then consider a separate synthetic fixture that reproduces the relocation shape; do not patch the CGM method based on mapping alone.

## Phase 40O — remaining original UI method disassembly (user-run, 2026-10-21)

Read-only llvm-objdump `0x6A5F60` through `0x6A5FB8` from previously verified native library. Important continuation:

```asm
6a5f60: ldr  x21,[x20,#0x30]
6a5f64: ldr  w8,[x0,#0xe0]
6a5f68: cbnz w8,0x6a5f70
6a5f6c: bl   0x4c8288
6a5f70: mov  x0,x21
6a5f74: mov  x1,xzr
6a5f78: mov  x2,xzr
6a5f7c: bl   0x1099830
6a5f80: tbz  w0,#0,0x6a5fa4
6a5f84: ldr  x0,[x20,#0x30]
6a5f88: cbz  x0,0x6a5fb4
6a5f8c: and  w1,w19,#1
6a5f90: ldp  x19,x30,[sp,#0x20]
6a5f94: ldp  x21,x20,[sp,#0x10]
6a5f98: mov  x2,xzr
6a5f9c: ldr  x22,[sp],#0x30
6a5fa0: b    0x1099a38
6a5fa4: ldp  x19,x30,[sp,#0x20]
6a5fa8: ldp  x21,x20,[sp,#0x10]
6a5fac: ldr  x22,[sp],#0x30
6a5fb0: ret
6a5fb4: bl   0x4c82ac
```

Observations: real method has conditional branches, calls, a return, and a tail branch. `BL` at 0x6A5FB4 might be an error path but not independently proven. A 16-byte copied trampoline crosses PC-relative `ADRP` at +12, thus invalid without relocation logic.

## Phase 40P — prepared separate fixture; not runtime validated

Prepared new archive `Phase40P-EntryBranch-Fixture.zip` for Windows local build. It contains standalone zero-network ARM64 test APK source in package `com.cgm.phase40p.fixture`, using the successful Phase40L build/runner design but replacing its single NOP with matching first-three stack-save instructions and a fourth `ADRP x22` in a fixture-only method. Test modifies only the **first four-byte instruction**, then replays that original `STR x22,[sp,#-0x30]!` in a trampoline, returns to **target+4** where subsequent instructions (including `ADRP` at +12) execute at their original PC. The target's original function increments a private counter. Three cycles of 8 calls are checked. Builds only on user's Windows SDK; no live result yet. Independent cross-assembly sanity check showed first-three encodings `f81d0ff6 a90153f5 a9027bf3` and fourth `ADRP x22`. No modification of the CGM APK or its runtime intended by 40P.

Limit: This fixture tests branch proximity and continuation shape inside its own ARM64 image only; real CGM code/hook ownership, runtime memory protections, thread safety, exact original-CGM calling context, membership checks, genuine upstream authorization, and garden access remain outstanding.

## Phase 40P — compiled and run: guarded **non-pass** (user console, 2026-10-21 16:03:46)

- Local independently signed no-network fixture APK SHA-256 `39CC50818BA5AF717C58D3CC19C2063B15BA695EA7806FCDE5AE0C5F05217360` installed on dedicated `127.0.0.1:62032`, package `com.cgm.phase40p.fixture`, fixture process PID `8638`.
- Result `SYNTHETIC_UI_PROLOGUE_MISMATCH`; `Prologue 3 stack saves and ADRP x22: no`.
- Fixture baseline/warm return values 7/7; original call count 1001.
- **0/3 patch cycles**, **0** hook calls, **0** pass-through calls, restored bytes `no` (not applied), target still modified `no`; `errno: 0`, `PASS: NO`.
- This is a **preflight mismatch**, not evidence that the actual branch or Houdini trampoline was attempted or failed; no CGM game code modified.
- Investigation: test binary `libcgm40p_fixture.so` in `build` must be disassembled with `llvm-objdump -d --disassemble-symbols=fixture_target` to compare the four compiled ARM64 instruction words to source-side preflight constants: `F81D0FF6, A90153F5, A9027BF3`, and `ADRP X22` mask `(word&9F00001F)==90000016`. Then distinguish compile/linker behavior from runtime memory/image mismatch before changing or reinstalling anything.

## Phase 40P linker-relaxation diagnosis and R1 fix (2026-10-21)

User executed **offline** llvm-objdump against compiled Phase 40P fixture `libcgm40p_fixture.so`:
```asm
1d8c: f81d0ff6 str  x22,[sp,#-0x30]!
1d90: a90153f5 stp  x21,x20,[sp,#0x10]
1d94: a9027bf3 stp  x19,x30,[sp,#0x20]
1d98: d503201f nop
1d9c: 10fffed6 adr  x22,0x1d74 <fixture_original_body>
1da0: d63f02c0 blr  x22
```

Source requests `ADRP X22, fixture_original_body; ADD X22, X22, :lo12:fixture_original_body` at +12/+16. LLD performed relaxation into `NOP; ADR` for the nearby fixture-local symbol. Thus preflight correctly failed on the fourth instruction. This is a compile/link layout issue, **not** a native patch/Houdini attempt. No CGM code changed.

Reproduced the same C/assembly transformation in a local LLVM 17 AArch64 ELF shared-library toolchain, and verified `-Wl,--no-relax` retained the expected `ADRP X22; ADD` pair. **Not** yet built under the user's Windows NDK.

Prepared dedicated R1 archive `Phase40P-R1-NoRelax-Fixture.zip`, a *separate* package `com.cgm.phase40pr1.fixture`, no Internet permission. It changes clang link flags to include `-Wl,--no-relax` and adds a **build-time disassembler assertion** for exact `STR; STP; STP; ADRP X22`. Build fails before APK signing if relaxed or altered. Its runner uses explicit serial `127.0.0.1:62032`, refuses an existing R1 package, and doesn't access/replace the previous Phase40P/CGM apps. R1 build and actual Nox test **not yet run**.
