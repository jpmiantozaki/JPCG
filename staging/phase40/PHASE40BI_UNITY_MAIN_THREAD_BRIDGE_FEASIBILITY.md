# Phase 40BI — Unity main-thread callback bridge readiness after Phase40BH

**Date:** 2026-10-23. **Status:** Read-only native metadata source audit and a proposed bounded test sequence. **No APK generated, patched, installed or executed in this checkpoint.**

## Verified Phase40BH result (Windows user logs)

Same disposable Nox62033 test APK SHA256 `C536D7E6F04D66FA4878E9D24F0788B6E4C62DD3B18DCC0329CCB50E6E444C78` from Phase40BF/BG. Native library loaded at 01:42:39.744. Native sampler 5-second intervals:
- Sample1 01:42:44.748 105 NULL
- Sample2 01:42:49.755 105 NULL
- Sample3 01:42:54.762 105 NULL
- Sample4 01:42:59.768 105 NULL
- Sample5 01:43:04.772 105 NULL
- **Sample6 01:43:09.779 110 PRESENT** (the user-reported JSON `before_sample_max=6` means this snapshot was **PRE-CLICK**).
- Sample7 01:43:14.783 110 PRESENT
- Sample8 01:43:19.789 110 PRESENT
- Sample9 01:43:24.798 110 PRESENT
- Sample10 01:43:29.805 110 PRESENT

`after_click_statuses=[110,110,110,110]`. One synthetic /authenticate.php config fetch and one local no-data TCP accept; real UI reached credential-entry view without any actual credentials or inner CONFIRM. **The singleton became non-null at least once before outer yellow LOGIN was pressed; no causal claim that button caused initialization is justified.** Presence held for 5 sample points, but not evidence of object pinning/GC safety, live component identity or Unity owner-thread access.

## New metadata review (user-owned Il2CppDumper dump.cs, original arm64 v1026)

- `LoginServerManager : MonoBehaviour` TypeDef3318; `<Instance>k__BackingField` static; `Awake()` RVA0x6A58E4; `OnDestroy()` RVA0x6A5A3C; `Start()` RVA0x6A5A84.
- `LoginServerManager.Login()` RVA0x6A86E8; `SendLogin(...)` RVA0x6A80C0; persistent inner CONFIRM PathID4300 -> Login() on component PathID4610, unchanged.
- Unity runtime `UnityEngine.UnitySynchronizationContext : SynchronizationContext` TypeDef4228, has `m_MainThreadID`, `Post(SendOrPostCallback,object)` RVA0x109A274, `Exec()` RVA0x109A430, static `InitializeSynchronizationContext()` RVA0x109A6FC, `ExecuteTasks()` RVA0x109A778. These are **managed implementation metadata**, not a captured live context instance, verified JNI-to-managed callback, safe delegate lifetime, or permission to invoke private Unity internals.
- A notification-specific `AndroidReceivedNotificationMainThreadDispatcher` is also present, but it is narrowly scoped to Android notifications, not a generic CGM login callback facility. No guaranteed public generic `UnityMainThreadDispatcher` was observed in the dump.

## Engineering decision: next required test is harmless Unity-owned-thread execution marker

No repeated NULL/PRESENT polling and no further replays of yellow LOGIN are needed. The next *legitimate* integration proof must show in a fresh disposable **test-signed** app version that:
1. New code has a stable lifetime-managed callback scheduled to the Unity owner thread after game startup (not merely Android UI thread / JNI-attached worker thread).
2. In the callback, only emit an innocuous thread-marker and check a stable Unity object/type reference; **do not** read username/password or dereference an unsafe managed object from background thread; never invoke `Login()`, `SendLogin()` or original CONFIRM.
3. A known valid Unity main-thread scheduling mechanism is identified (prefer an authorized Unity project source C# `MonoBehaviour.Update` or existing app-supported plugin integration; generic UnitySynchronizationContext exported symbols by themselves are insufficient).
4. No modification of original PathID4300 persistent `Login()` listener before safe staged replacement/rollback and once-only continuation are proven, with stale response/account-switch/timeout fail-closed.
5. Preserve official authentication, independent ANLGarden VIP, Game and Garden responses; no fabricated session provider or third-party access bypass. Protect Nox62030/31/32; Nox62033 disposable only.

**No current installable Phase40BI test package exists.** New test requires a defensible in-game Unity event insertion mechanism, not just IL2CPP method pointer or Singleton pointer. Future tests must use explicit human READY/CLICKED for UI where relevant, never fixed sub-minute timeout.
