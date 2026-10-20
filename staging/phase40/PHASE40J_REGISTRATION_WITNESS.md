# Phase 40J — Registration route reconciled with Phase 25V

**Outcome: static method identity verified independently of the absent reflection API. Real translated-runtime interception remains unverified.**

Date: 2026-10-21. Source evidence: prior Phase 2.6G metadata/ELF registration report; user-provided Phase 25V runtime JSON; same native APK library fingerprint.

## Independently verified by offline executable verifier

- APK examined: `CGM-62031-Local-SIGNED.apk`, SHA-256 `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`.
- ARM64 `libil2cpp.so`: SHA-256 `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`.
- Metadata v31, Assembly-CSharp.dll codegen module from relocated code-registration module array, module count 45 and method-pointer count 2990.
- `Login()`: declaring type `LoginServerManager` (3318), metadata index 22682, token `0x060006D6`, method pointer slot 1749 => RVA `0x6A86E8`.
- `SendLogin(string,string,string,bool)`: same declaring type, metadata index 22676, token `0x060006D0`, method pointer slot 1743 => RVA `0x6A80C0`.
- Native executable prologues match the Phase 25V observer's reported bytes.
- Phase 25V candidate VAs `0x5A586E8` and `0x5A580C0` imply one common candidate base `0x53B0000` (candidate addresses only; **not** verified callable ARM64 entrypoints under the Nox translator).
- Phase 25V's scanned runtime has no `System.RuntimeMethodHandle.GetFunctionPointer`; its reflected native address is NULL for both. This does not contradict the ELF's static method-pointer table.
- Original 25V final JNI handle drain was balanced, and active diagnostic succeeded; no game hook or login was executed.

## Explicitly NOT proven

1. Actual runtime registration table ownership or executable call coverage in Nox/Houdini.
2. Guest-code translation-cache coherence, inline detour safety, ABI correctness or exception-safe trampoline.
3. All original credential submission routes (including saved/web login, reconnect, and scene reload) are covered.
4. A compiled APK performs CGM ACTIVE gating before the original login.
5. Genuine PlayPark session or Garden authorization independent of legacy relay.

## Engineering decision

Stop attempting to resolve the missing reflection function through repeated S/T/U/V runs.
Keep registered RVAs as the **file-backed identity mapping**, but do not use them alone to justify a live inline patch.
The next *different* proof must exercise a controlled, benign pass-through on a matching **native ARM64 execution environment** or establish a different supported application-level extension entry. Nox x86_64 hosts translated ARM64 guest code and is an additional detour-validation obstacle.
Only after benign guest execution, rollback and all-route coverage should an asynchronous CGM-only eligibility check be connected; official PlayPark credentials, login responses and sessions remain unmodified.

## Reproduction

The offline verifier `phase40j_registration_witness.py` was executed successfully against the original submitted signed APK and Phase 25V JSON; three deliberately corrupted input scenarios all failed closed.
To inspect the *currently installed* `44B89AA...` build, rerun the verifier locally with `D:\CGMTEST\CGM-62031-installed-check.apk` and the saved Phase 25V JSON. It requires Python standard library only and makes no network/ADB/Nox calls.

**No production updates, no APK installs, no Nox access, no game-code changes made during Phase 40J.**
