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
