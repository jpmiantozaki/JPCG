# Phase 40AR — Offline original-client staging signature & safety gate

**Date:** 2026-10-22. **Status:** Windows PowerShell sign-and-verify script assembled, Python content comparator regression tests PASS. **Windows signing NOT RUN; APK NOT INSTALLED.** This checkpoint builds on user-confirmed Phase40AQ offline unsigned merge PASS, not a new separate synthetic Unity app.

## Input evidence

User Phase40AQ: original installed Nox62032 local copied APK SHA256 `44b89aa390734b2d05705a20620d6175e298ed790d88734d245e87d98ac00944`, unsigned staging SHA256 `9d2a429456ae8353be4a70d2b8c037c08c4e2c65e2f10c7b4df182d9e617af1d`, 167 original entry payloads preserved, 3 old signature metadata removed. Phase40AP input Java preloader DEX SHA `a055bcec754d83004049b5640788b99fcc8f47887d632d1e4fabf1e0eb84873e`, ARM64 JNI gate ELF SHA `037a6a9ca3266e4d4ad47d2b4ee53ddd3d71c1df6388793bbea11d04df8a0a36`. This is a **packaging and loader candidate**, not the actual Unity CONFIRM replacement.

## New artifact

`CGM_Phase40AR_Offline_Signing_Readiness.zip` SHA256 `0bea795ffa41401379503b95da4d34a2423854dc53bb259e5f7641aa2ac0761d`. Contains:
- `scripts/run_phase40ar_sign_only.ps1` one-command Windows script. Uses locally installed Android zipalign/apksigner/aapt and JDK keytool. Pins hashes of Phase40AM original local APK and Phase40AQ unsigned copy. Creates **new, separate test-key-signed APK** (never modifies original). Shows original/new certificate SHA256 digests; packages must retain `com.DeathToB.ANLGarden`.
- `scripts/verify_phase40ar.py` checks 167 original ZIP payload hashes plus `classes2.dex`/ARM64 gate remain exact after signing. Reports JSON to staging-only directory; detects input hash or method tampering.
- README and synthetic regression test. ZIP CRC PASS; Python synthetic signed-content parity and tamper-rejection test PASS; Python syntax PASS.
- Signed APK Windows build and APK signature verification: **NOT TESTED** here (Windows SDK required).

## User one-command next step

Extract under `D:\CGMTEST\CGM_Phase40AR_Offline_Signing_Readiness` and run:
```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File 'D:\CGMTEST\CGM_Phase40AR_Offline_Signing_Readiness\scripts\run_phase40ar_sign_only.ps1'
```
Expected output includes `PHASE40AR SIGNED CONTENT PARITY PASS`, `PHASE 40AR SIGNED OFFLINE ARTIFACT PASS`, original and test-only certificate digests. No ADB/install, any emulators, login, network or session calls.

## Deployment go/no-go

This staging build intentionally shares the same package ID as the original game but uses a separate test-only signing key. **If fingerprints differ it cannot update the currently installed game while preserving its app data.** Do not install to Nox62032 or protected Nox62030/62031. Later controlled runtime proof requires a new empty disposable test emulator, explicit install/restore plan, and runtime logcat marker `CGMPhase40AP` to prove the class/library loader ran. Even successful preloader log is NOT proof of gate coverage or proper Unity callback control; original Path4300->Login() target Path4610 must not be disabled before an executable safe replacement is proven. Preserve upstream ANLGarden VIP and official PlayPark/Game/Garden authorization. Never share the local test keystore.

## User's Windows Phase40AR result — PASS (2026-10-22)

The user ran `run_phase40ar_sign_only.ps1` and reported successful zipalign, apksigner verification (v1/v2/v3), original and staged same `com.DeathToB.ANLGarden` versionCode1/versionName1026, and 167 original entries plus staged DEX/ARM64 library payloads intact. The original certificate SHA256 is `9f9f13f2ebeda8ada9665daa9499cd3859cae64a1a87de42efa62735c3ab3a23`; **test-only** new signing certificate SHA256 is `6fef57b71ce2f70af9cca1df6578620462ec55065a9151f78d0194386ebf25ed`. Signed test APK SHA256 `446befe5692a552d17e8d1a6bf799e838112be62e50abac101de8b08eab18ee1`, located on Windows under `D:\CGMTEST\CGM_Phase40AR_Offline_Signing_Readiness\staging-only\Phase40AR_CGM_Preloader_SIGNED_TEST_ONLY.apk`.

**No runtime test or installation occurred.** Due different certificate, the signed test APK must not be installed as an update on Nox62032 or protected Nox62030/62031. Phase40AS is a *separately authorized, fresh disposable instance only* Unity-process loader smoke test. Not a working CGM Auth-First APK.
