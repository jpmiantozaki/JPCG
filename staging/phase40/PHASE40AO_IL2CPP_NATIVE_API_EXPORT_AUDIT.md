# Phase 40AO — Original IL2CPP exported C-API boundary (read-only)

**Date:** 2026-10-22. **Status:** Static symbol/disassembly audit of archived original CGM client, without executing native APIs, altering the app or Nox, or contacting any third-party server.

## Phase40AN closed
User's Windows comparison of the Nox62032 installed `classes.dex` against the archived reference reported exactly 1 implemented method instruction difference among compared methods: `Landroidx/core/graphics/PaintCompat;->hasGlyph(Landroid/graphics/Paint;Ljava/lang/String;)Z`; 0 added/removed classes, 0 added/removed implemented methods, 0 flagged relevant changed methods, no loader-literal changes reported. This method is AndroidX text graphics, not the native JNI gate loader or Unity Login() callback. Comparison coverage does not establish complete DEX equivalence.

## Direct original native ELF export findings
Inspected **actual archived** `lib/arm64-v8a/libil2cpp.so` using readelf and llvm-objdump; SHA256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`. Phase40AM already confirmed byte-identical installed and archived native library, so these symbols also exist in the installed file.

AArch64 exported functions present:
- `il2cpp_domain_get` RVA `0x56d80c`
- `il2cpp_assembly_get_image` RVA `0x56d1d8`
- `il2cpp_class_from_name` RVA `0x56d210`
- `il2cpp_class_get_method_from_name` RVA `0x56d238`
- `il2cpp_runtime_invoke` RVA `0x56dc28`
- `il2cpp_thread_current` RVA `0x56dc5c`
- `il2cpp_thread_attach` RVA `0x56dc60`
- `il2cpp_gchandle_new` RVA `0x56da74`
- `il2cpp_gchandle_free` RVA `0x56dafc`
- `il2cpp_object_get_class` RVA `0x56dbd4`

The entrypoints `il2cpp_class_from_name`, `il2cpp_class_get_method_from_name`, and `il2cpp_runtime_invoke` disassemble to valid direct AArch64 `b` branches at their exported addresses: `0x56d210 -> 0x4cbb48`, `0x56d238 -> 0x4c93d8`, `0x56dc28 -> 0x4bf2c4`. This shows real compiled native C-API entrypoint stubs rather than absence of exports.

## What is (and is not) established
The missing `System.RuntimeMethodHandle.GetFunctionPointer` from old Phase25V is **not** evidence that these *separate* native IL2CPP C APIs are absent. An authorized, safely loaded native module may be able to use documented IL2CPP metadata/runtime APIs to discover managed methods and invoke application callbacks, but **symbol presence is not a working bridge**, nor proof of an actual LoginServerManager instance or Unity owner-thread validity.

**NOT proven:** native module loader inside original APK; real onClick interception/replacement that suppresses the current persistent Login() until ACTIVE; safe access to target managed component; GC/thread/lifecycle behavior; single continuation preserving entire original Login(); direct mobile HTTPS membership policy; valid server-side membership and official upstream acceptance.

Original scene remains PathID4300 persistent `Login()` -> component PathID4610, state=2. Existing JNI native gate and negative-path HTTPS tests were conducted only in isolated Android test APKs. Changing persistent listener to Off without safe replacement blocks the app. No upstream VIP rejection, game packet, PlayPark or Garden session decision may be bypassed.

## Go/no-go
**GO:** Targeted further read-only ABI/entrypoint analysis (no new login probe needed), and source-level Unity integration if matching authorized project or developer extension interface is supplied.

**NO-GO:** Claim that `il2cpp_runtime_invoke` alone intercepts CONFIRM or install a modified CGM APK. Need an independently validated, authorized app-level native/Unity execution bridge and correct Unity thread/managed-object handling before any test deployment. Never access rollback Nox62030/62031.
