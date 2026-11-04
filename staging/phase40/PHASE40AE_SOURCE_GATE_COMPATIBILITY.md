# Phase 40AE — Standard CONFIRM source-adapter compatibility

**Status:** Tested Java/Unity source adapter only. **NOT integrated into installed CGM APK**, not a live login test or production backend change. Recovered pre-existing code from the user's own archived `CGM-AuthFirst-Phase25W(2).zip`; avoided another unrelated upstream prototype.

## What changed

1. `EmailProtocol.normalizeEmail` now preserves the **domain's original case** because JPCG `codes.login_id` has a case-preserving exact-match lookup. Previous Java implementation silently converted `Case@EXAMPLE.INVALID` to `Case@example.invalid` and could falsely declare a redeemed exact-match identifier INACTIVE. The source adapter still validates the format and trims leading/trailing whitespace; this change does not establish account ownership, official provider authentication, or any automatic alias conversion.
2. `UnityEmailGate` keeps its production public constructor taking a `Port` and `membershipUrl` (HTTPS-only), and adds a **package-private fixture-only constructor** to inject the already-defined `EmailMembershipGate.Transport` and close callback. That lets us test the **actual Java-to-Unity source bridge's** asynchronous behavior without issuing network requests.
3. New bridge fixtures use prefetched/autofilled and manually typed email values and demonstrate the bridge reads the **current UI field**, sends only claimed email for membership, waits for a fresh ACTIVE decision, schedules original callback via the Unity-owner-thread pump **once**, and ignores duplicated, stale, changed-email and INACTIVE results. No password or game packet flows through the preflight adapter.

**Test results:** `62/62` original+extended Java protocol/policy fixture assertions and `16/16` new real-bridge fixture assertions; `78/78` total. Built with `javac --release 8 -Xlint:all,-options -Werror` using the local JDK. Source archive `CGM_Phase40AE_Source_Gate_Compatibility.zip` SHA-256 `afde2c671e43e2b155cf23de2bec0721ac0582e5ed33eceb795f00c558df599c`. Includes Java sources, archived source-level Unity C# template, tests/run.sh, tests/run.ps1, test-results.txt and full instructions. No Android DEX, APK, signed build, or Unity runtime testing performed.

## Exact existing APK wiring, unchanged

- Outer yellow LOGIN: Button Path ID `4164` invokes `ConnectToServer(false)`.
- Inner normal CONFIRM: Button Path ID `4300` invokes `Login()` (RVA `0x6A86E8`) using **one persistent callback**.
- InputField Path ID `4121` holds currently typed or remembered email, and password InputField Path ID `4724` remains entirely outside membership preflight.
- Original `Login()` calls unchanged `SendLogin()` at RVA `0x6A80C0` via callsite `0x6A8868`, then sets `isLoginProcess=true`. The FB/WebView alternate route is user-confirmed obsolete and out-of-scope.

The recovered `CgmEmailLoginGate.cs` **requires inner Button persistent listener count 0** and a separate preserved original-login UnityEvent with exactly one `LoginServerManager.Login()` target; consequently it **would reject the currently compiled APK's wiring**, which still invokes `Login()` directly. Adding Java or an extra listener cannot solve this. The needed next action is a *verified actual* Unity-source integration or independently demonstrated ARM64 guest-native pre-submit adapter, plus reentrancy/lifecycle/owner-thread audit. The matching original game Unity source project is not available; `dump.cs` does not contain original C# method bodies.

No Nox, original APK, PlayPark, ANLGarden, production Render or main branch were touched. CGM ACTIVE grants permission to attempt original login **only**; ANLGarden may independently reject VIP and original PlayPark/Garden authentication always remains authoritative. Do not rewrite a third-party VIP rejection into success or use fake packets/sessions.

## Reproduce offline

Unzip the Phase40AE source archive, then use either `tests/run.ps1` (Windows PowerShell with JDK) or `sh tests/run.sh` (macOS/Linux). These commands compile and test Java only; there is no network or game connection.
