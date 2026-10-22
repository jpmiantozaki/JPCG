# Phase 40BJ — Android UnityPlayer native-render-loop Java callback marker

**Date:** 2026-10-23. **Status: SOURCE/STAGING PACKAGE READY; Windows build, APK update and actual Nox62033 runtime **NOT TESTED**.** Continues from user-confirmed Phase40BH live manager field transition. Does not implement Auth-First inside original app.

## Previous user-proven checkpoint (40BH)
Phase40BF test APK on *disposable Nox62033* signed SHA256 `C536D7E6F04D66FA4878E9D24F0788B6E4C62DD3B18DCC0329CCB50E6E444C78`. Native static `LoginServerManager.<Instance>k__BackingField` snapshots: 5 consecutive NULL (status105) followed by 5 consecutive PRESENT (status110), the first PRESENT **before user clicked** outer yellow LOGIN (`before_sample_max=6`). Four post-click snapshots PRESENT. Local synthetic config 1 request, no-payload TCP listener 1 accepted connection, login credential-entry UI visible with no input/CONFIRM. The BF experiment does not prove GC lifetime, main-thread access or managed callback execution.

## NEW static original DEX finding
Independently parsed **actual archived original** `classes.dex` SHA256 `1719e3f09ae56a531116634967fd7106de384e3d282fa212174ed5cf46844140` from input APK SHA256 `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`. Phase40AM previously proved original scene/native content preserved on Nox62032 and staging overlays preserve same 167 original ZIP-entry payloads.

- `UnityPlayer.currentActivity`: public static Android Activity field.
- `UnityPlayerActivity.mUnityPlayer`: protected UnityPlayer instance field.
- `UnityPlayer.m_MainThread`: `com.unity3d.player.y0`, class extends Thread, has its own Looper.
- `UnityPlayer.m_Events`: Java `ConcurrentLinkedQueue`.
- Original `UnityPlayer.queueGLThreadEvent(Runnable)` code checks current thread vs `m_MainThread`; on other threads enqueues into `m_Events`.
- `UnityPlayer.executeGLThreadJobs()` polls `m_Events` and invokes `Runnable.run()`.
- Original `com.unity3d.player.y0.run()` configures a Looper with callback `x0.handleMessage`, whose code invokes both `executeGLThreadJobs()` and `nativeRender()`.
This is a **specific Unity engine loop queue API**; not proof of `UnitySynchronizationContext.Post` executing a managed C# delegate.

## New Java-only harmless runtime test kit
Conversation artifact `CGM_Phase40BJ_Unity_Loop_Thread_Marker.zip`, SHA256 `bc061d85c8c92711f9607af03229c3c13ba2070395e38d25a0e9e491d8fe5638` (13 entries; CRC PASS). Includes:
- Rebuilt Java `com.unity3d.JavaPluginPreloader` which loads existing original native gate library (does not invoke its JNI functions) and waits/polls for UnityPlayer currentActivity/mUnityPlayer/m_MainThread. Invokes **only original Java `queueGLThreadEvent(Runnable)`**, scheduling a harmless Java log marker. Marker `PHASE40BJ_UNITY_LOOP_THREAD_MATCH_PASS` requires `Thread.currentThread()==m_MainThread` and differs from Android main UI thread. Does NOT access/dereference any managed Unity LoginServerManager instance; does not touch game credentials, original Login(), SendLogin(), CONFIRM listeners, official PlayPark or Garden auth.
- Native Phase40AH gate and Phase40BF read-only probe source remain SHA-identical to prior package, built merely for library compatibility (native snapshot probe is unused by new Java callback).
- Windows `scripts/build_update_phase40bj.ps1` expects exact Phase40BF test-signed **local and currently installed** APK SHA256 above on Nox62033, source Nox62032 read-only archived snapshot hash `44B89AA3...`, same Phase40AR test certificate SHA256 `6fef57b71ce2f70af9cca1df6578620462ec55065a9151f78d0194386ebf25ed`, 167 preserved original entries plus two new overlays, a separately signed install and post-install ADB pull SHA verification. No original rollback/production ports allowed.
- One-command `run_phase40bj_full.ps1` launches only disposable original Unity Activity and uses manual START then **READY** (no sub-minute fixed load timeout), without clicking yellow LOGIN, reading credential fields or contacting membership/official endpoints by test code. Emits JSON and filtered log; **only Java loop thread callback is tested**, not managed C# owner-thread callback.
- Optional exact-hash-guarded rollback script to prior BF test signed APK on Nox62033.

## Offline tests performed
- Java marker source compilation with Java 8 target against Android Handler/Looper/Log stubs: **PASS**.
- Static script/target/source-integrity checks **4/4 PASS**.
- Original four native gate/JNI/instance probe C files SHA-identical to original BF kit: **PASS**.
- Signed ZIP entry-preservation verifier synthetic 167-entry fixture PASS and deliberate original-content tamper rejected: **PASS**.
- New project ZIP CRC PASS.
- Original archived DEX method/field dispatch path inspected directly.
- **No actual Windows Android SDK/NDK signing build, Nox62033 package update, queued callback runtime, GC/IL2CPP managed action or original login/Garden session was tested here.**

## One-command Windows instruction
Extract zip to `D:\CGMTEST\CGM_Phase40BJ_Unity_Loop_Thread_Marker`. Keep Nox62033 running with Phase40BF exact signed test APK installed. Run:
```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40BJ_Unity_Loop_Thread_Marker\scripts\run_phase40bj_full.ps1'
```
Follow START, wait for initial yellow LOGIN screen fully loaded and type READY, **do not click LOGIN/CONFIRM or enter credentials**. Send runtime output or `runtime-results\Phase40BJ_Runtime_Result.json` and `Phase40BJ_Thread_Marker_Log.txt`. Need `PHASE40BJ_UNITY_LOOP_THREAD_MATCH_PASS` to conclude native Unity loop Java callback, but not enough for managed Unity thread or Auth-First gate.

**Important:** If update succeeded but script later stopped, DO NOT rerun old-BF installer over unexpected APK. Inspect installed SHA and use guarded rollback only intentionally. Protect Nox62030, 62031, 62032.

## User actual Phase40BJ Windows/Nox62033 run — build PASS, queue marker NOT YET OBSERVED (2026-10-23)

User ran `scripts/run_phase40bj_full.ps1` on disposable Nox62033. Their console reports `=== PHASE 40BJ GUARDED BUILD AND UPDATE PASS ===`, verified exact newly installed signed APK SHA256 `653FEDD22DAD578DCC42382AB34AB0201D585EA19B8A69E7D9DA97F2EA698BE5`. User typed START, waited until Unity initial yellow LOGIN UI was ready, typed READY, and collected logs. Terminal result: `NO_QUEUE_MARKER_OR_SETUP_INCOMPLETE`; console did not display any `PHASE40BJ_` tagged lines. **No evidence that a Java Runnable executed, and no evidence yet as to why.** User's `runtime-results\Phase40BJ_Thread_Marker_Log.txt` and `Phase40BJ_Runtime_Result.json` have not yet been supplied.

**Important source review:** `run_phase40bj_full.ps1` waits just 12s after READY before taking a one-time `logcat -d` snapshot. The Java preloader initially schedules a test after 15s and retries every 20s, up to 15 attempts. An initial/class-loading issue, a queue reflection failure, a callback not being drained, a logger-filter failure or insufficient observation time are all possible; cannot distinguish without logs. `JavaPluginPreloader.java` initializes static `Handler UI=new Handler(Looper.getMainLooper())` *before* the guarded static block, which is a candidate for class initialization error if main Looper unavailable, but no runtime evidence supports that specific cause yet.

**Next diagnostic is log-only, NOT a repeat install**: keep exact 40BJ test APK on Nox62033, read existing results report/tagged log and current `adb -s 127.0.0.1:62033 logcat -d` filtered to `CGMPhase40BJ`, `JavaPluginPreloader`, `ExceptionInInitializerError`, `ClassNotFoundException`, `NoClassDefFoundError`, `UnsatisfiedLinkError`, `AndroidRuntime`. Have user paste filtered output after reviewing sensitive details. No touching protected instances 62030/31/32, no login/CONFIRM/credentials, no tests with remote upstream. Do not claim native owner-thread callback PASS.
