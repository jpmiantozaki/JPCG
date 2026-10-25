# Phase 40BU — Original ARM64 Login deny-only branch runtime preparation

**Checkpoint:** October 25, 2026  
**Status:** **offline original-client patch and test harness verified; NOT signed, installed or run on Nox.**  
**Previous proven:** Phase40BP in-APK CGM ACTIVE recognition; Phase40BS one-shot local ordinary submission; Phase40BT precise existing `Login()` return epilogue.

## Exact test-only native change

The original `LoginServerManager.Login()` from the verified ARM64 `libil2cpp.so` has `SendLogin()` at RVA `0x6A8868` followed by `isLoginProcess=1` at `0x6A8870`, then an existing original method return epilogue at `0x6A8874`.

The test-only patch changes exactly one original instruction, **not** a credential or game-session structure:

```text
original 0x6A8868: 97fffe16  bl 0x6A80C0   (SendLogin)
test     0x6A8868: 14000003  b  0x6A8874  (existing epilogue)
        0x6A886C: 52800028  mov w8,#1     (skipped)
        0x6A8870: 39012268  strb w8,[x19,#0x48] (skipped)
        0x6A8874: a9427bf3  ldp x19,x30,[sp,#0x20]
```

Endian bytes: original `16feff97`, replacement `03000014`. The native SHA256 is pinned to `fb569843b5955b0bbbebc143d5421942160d4dfe6f70cb7853e85cace8b02fc8`. Fresh bounded `llvm-objdump` of the **actually patched local copy** confirmed `b 0x6a8874` (no Android runtime execution yet). Python ELF RVA-to-file-offset mapping and SHA guard reject different images.

**This is an unconditional DENY experiment** for the normal original CONFIRM route, including CGM ACTIVE identities. It is **not** a conditional native CGM membership guard or usable login build. The unsupported/legacy Facebook callback remains out of scope and unmodified. `Login()` may initiate a connection earlier; this experiment checks no credential packet/no `isLoginProcess` write along the targeted path, not all transport side effects.

## New local artifact and validation

`CGM_Phase40BU_Native_Deny_Path_Proof.zip` — **22,075 bytes**, SHA256:
`eface7da6c88d1184aa0ab2baae2a4a4910dcfb491c9badeaaed0e7c061262bc`

The archive contains **source only**, not a signed APK, injected library, NDK, private packets or signing keys:

- Exact-hash ELF patcher; verifies original instructions around the return and patches only one instruction.
- Build/install PowerShell script that **defaults to build-only**. Explicit `-InstallTo62033` requires installed *exact* Phase40BP signed APK hash `1941AB5D7E2D00C16B71C6B6139AE8869CF3CEEF0424245E18C6F8B319BD14E4`; preserves a copy before installation.
- APK payload verifier: all existing ZIP payloads unchanged except original arm64 `libil2cpp.so`; re-sign using the *existing disposable test signer*, with exact certificate and app ID checks.
- One local path-only fixture that temporarily routes **only Nox 62033** `8091` to a localhost probe, asks user to click CONFIRM once with **fictional password**, expects `config=1/session=0/other=0`, denies any `/session.php` request, logs no credential-bearing URL, and restores original reverse mapping.
- Separate exact-hash guarded restore script to return to the original installed Phase40BP signed APK immediately after the test.

**Executed offline:** **12/12 Python tests PASS**, Python compilation PASS, exact patched-ELF disassembly, synthetic ZIP one-entry-difference validation, false-positive/overwrite/hash guards, local request query privacy. No Android build/installer/PowerShell execution has yet been performed.

## Next requested bounded device test (not run yet)

On **disposable Nox `127.0.0.1:62033` only**:

1. Build from the exact installed Phase40BP source and optionally install the deny-only test APK with `-InstallTo62033`, verifying installed SHA.
2. Start the existing PHP8094 and TCP18992 fixtures plus the bundled path-only `run_phase40bu_local_deny_probe.py`.
3. After genuine CGM ACTIVE and `CGM_VIP_ORIGINAL_CONFIRM_ENABLED`, click original CONFIRM once **with a fictional password**. Record whether it visibly clicked, the `CGMPhase40BP` markers, and `config/session/other` counts. Zero requests alone without witnessed click is **inconclusive**. Do not send real credentials.
4. Restore Phase40BP using the explicit exact-hash restore script; preserve both output reports. If a STOP occurs, do not force/retry/install on other devices.

Only a successful, witnessed deny test would demonstrate that **a direct one-instruction branch alteration in this original ARM64 game client** executes as expected under Nox translation; it does not validate a multi-instruction trampoline, Java/JNI callback from the detour, one-use CGM sender authorization, or PlayPark login.

## Subsequent objective

Integrate the previously compiled Phase40BR one-use, identity/epoch/TTL-bound CGM ACTIVE approval at the actual Login/Sender boundary; allow original `SendLogin()` unchanged once, and on deny take the existing return without setting `isLoginProcess=1`. That remains **NOT IMPLEMENTED**, as does the independent compatible official relay processor. No synthetic positive game sessions or upstream VIP bypass are justified by this experiment.

**No main/default branch change, production mutation, Render/Cloudflare deployment, official login, PlayPark token handling, Nox device access or rollback `62030` access occurred during this offline checkpoint.**
