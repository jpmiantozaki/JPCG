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

## Runtime observation: expected DENY failed (user-reported 2026-10-25)

**New user-supplied Phase 40BU path-only probe output:**

```text
PHASE40BU_SAFE_FIXTURE_ARMED_N0X62033
PHASE40BU_CONFIG_REQUEST_SEEN
PHASE40BU_SESSION_ENDPOINT_REQUEST_SEEN count=1
PHASE40BU_DENY_ONLY_FAILED_SESSION_REQUEST_SEEN
PHASE40BU_COUNTS config=1 session=1 other=0
PHASE40BU_ORIGINAL_CONFIGURATION_ROUTE_RESTORED
```

**Interpretation:** the local fixture received **one** `/session.php` request and intentionally rejected it. The predicted zero-request state was **not observed**. The helper reports that reverse routing was restored. This is a **failed deny-only expectation**, not proof that the patched ARM64 branch executed or failed to execute.

**Important unresolved build identity:** the user supplied only the probe output, not the prerequisite build/install terminal marker `PHASE40BU_DENY_ONLY_INSTALLED_EXACT_APK_62033`. Inspection of `run_phase40bu_local_deny_probe.py` reveals that it checks the package exists using `pm path` but **does not verify its installed APK hash or embedded patch bytes**. Thus the probe can run against the previously installed Phase40BP, or another package, and report a false native-branch failure. **Do not repeat the login test until installed APK identity is verified read-only** by pulling only the `62033` base.apk and comparing its SHA-256 to the locally built `Phase40BU_SIGNED_TEST_ONLY.apk` and the exact Phase40BP hash `1941AB5D7E2D00C16B71C6B6139AE8869CF3CEEF0424245E18C6F8B319BD14E4`.

**Second harness bug:** `main()` prints `PHASE40BU_DENY_ONLY_FAILED_SESSION_REQUEST_SEEN`, but its final return expression is `2 if cleanup_failed else 0`. Therefore a failed deny expectation can return **process exit code 0** if cleanup succeeds. An exit code of 0 does **not** mean the deny test passed. Future probes must fail when unexpected session requests are observed.

**Next actions:** preserve the fixture output and native code, perform the read-only installed-APK hash check; if exact Phase40BU is installed, use the already supplied exact-hash restore script to return to Phase40BP on `62033` without another login attempt. If Phase40BP is still installed, do not run the Phase40BU restore script. If installed hash is unexpected, stop instead of overwriting any build. Further native runtime diagnosis depends on this identity result. No actual official PlayPark auth, Garden session, production deployment, or rollback Nox activity is implied.

## Follow-up installed-build identity result (user, 2026-10-26 00:52 UTC+08)

The user completed the read-only installed APK SHA comparison on disposable `127.0.0.1:62033`, obtaining:

```text
RESULT: PHASE40BU_CURRENTLY_INSTALLED
```

This means the **currently installed APK** matches the exact locally built `Phase40BU_SIGNED_TEST_ONLY.apk`, not the prior exact Phase40BP SHA. The archived Phase40BU build verifies that only `lib/arm64-v8a/libil2cpp.so` differs in payload, and within that file the intended 4-byte call-site instruction is changed. This check establishes **installed artifact identity, not observed execution of that instruction**.

The prior path-only probe already observed `config=1 session=1 other=0`; therefore Phase40BU remains a **failed deny-only experiment**. It is no longer reasonable to assume Phase40BP is *currently* installed. A timestamp of exactly when the BU install occurred was not included in this follow-up; comparison now alone cannot prove which build was installed at the instant the earlier probe recorded the request, although no intervening install has been reported.

**Remaining hypotheses, unverified:** app execution may not have reached patched `Login()` callsite; the HTTP request may have originated via another native send route or a previously queued request; or the guest-executed code may not have corresponded to the patched ARM64 instruction under the emulator. Path-only request counts cannot distinguish these. Do **not** call this evidence of an automatic VIP bypass or official PlayPark authentication.

**Immediate safe action:** preserve the prior reports, optionally record only the running process's `libil2cpp.so` mapping/architecture (no raw network logs), then run the **existing exact-hash guarded Phase40BP restore script** on 62033. Do not repeat the test or patch additional native instructions until the execution path is attributed. Rollback 62030, production, and main branch remain untouched.
