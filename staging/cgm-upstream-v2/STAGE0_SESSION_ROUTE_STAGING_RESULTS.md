# CGM Upstream Admission v2 — staging PHP session route

**Date:** 2026-10-23. **Status:** staged source package built and PHP tests PASS; no Render/Cloudflare deployment, no new Nox/APK operations, no real credentials.

## Deliverable

Conversation downloadable artifact: `CGM_Upstream_Gated_Staging_v2.zip`, SHA256 `c96eadaf8e6ee2a763ad4131e014c8a2d0f2c7abe235519f2c48dfb801167fe6`. The ZIP contains the following three ready-to-review PHP files under `patch/public/`: `_cgm_stage0_vip_gate.php` (retained previous v1 parser), `_cgm_session_admission.php` (admission policy) and `session.php` (staging copy of current JPCG entrypoint, with flag-gated admission). It also contains `tests/run.php`, test output and a SHA256 manifest.

Host PHP8.4 lint: 4/4 files clean. **32/32 synthetic tests PASS**. Original Android/Unity code unchanged.

## Behavior

The active flag `CGM_STAGE0_GATE_ENABLED=1` is **off by default**; disabled route behaves like existing JPCG public/session.php. With flag on, stage0 `session.php?data=` protected envelope is verified using existing JPCG `cgm_decrypt_packet`. Its first length-prefixed `INPP.` identifier is extracted while remaining original password/version fields are skipped and never logged. `cgm_membership_lookup` performs exact DB lookup; no implicit prefix alias or unauthenticated identity assertion is treated as server-verified login.

- INACTIVE/EXPIRED: encrypted `error=true,message=VIP,session='',data=''` reply; original no-VIP path.
- invalid packet/unsupported stage/backend failure: encrypted fail-closed service unavailable.
- ACTIVE: `FORWARD_UNCHANGED` only when `CGM_UPSTREAM_SESSION_MODE=existing_origin` **and** `CGM_ORIGINAL_ORIGIN` explicitly configured; pre-existing bridge then forwards the original untouched packet. This does NOT remove the BASE upstream VIP dependency and does not grant any game session.
- ACTIVE without compatible processor configured: fail closed; no fake `error=false`, session or login success.
- all follow-on relay stages fail closed under strict stage0 gate until genuine provider/session binding is implemented.

## Critical remaining gap

The known client `MGRelayResponse` JSON fields are `error,message,data,servertype,session,vip_expiry`, but the APK does not contain the MOD server-side method that supplies *genuine game-session data* to those fields. Thus knowing field names does not give an independent session issuer. The ZIP intentionally DOES NOT implement, infer, copy or forge a genuine game session. A JPCG-owned membership lookup is working logic, not equivalent to full official login.

## Next exact action

Run only the offline test using synthetic fixture bytes. After review, copy the three `patch/public/` files to a **local staging checkout** of the JPCG repository; do not deploy or set feature flag in production. Real `/session.php` requests are password-bearing GET queries, so configure query-string redaction for any future permitted live traffic. Do not enable stage0 feature in a public deployment until request logging, session state, backend authority and end-to-end compatibility are solved.

This report supersedes exploratory Java/JNI/Unity work as the immediate route: focus on server admission and complete compatible session processing, without altering Android client code.
