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
