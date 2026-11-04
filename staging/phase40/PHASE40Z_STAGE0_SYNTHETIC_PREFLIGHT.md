# Phase 40Z — Synthetic stage-0 CGM membership preflight

**Status:** Local offline feasibility experiment, not an installed APK or deployed upstream. 15/15 synthetic checks passed in PHP 8.4. No Nox, live login, provider credentials or server traffic. Real server-side VIP requirements remain in force.

## Why this route is worth examining

Existing CGM's second CONFIRM calls `Login()` and `SendLogin()`, sending the unmodified original credential packet within protected `packetHex~stage~relaySession` to `session.php`. Rather than first rewriting Unity IL2CPP, a **staging-only CGM-owned upstream preflight** could distinguish an initial stage-0 login and look up the **claimed** prefixed account identifier in CGM's database before deciding whether to relay that unchanged protected request onward. This is an alternative feasibility path, **not yet wired** to any HTTP endpoint.

On CGM ACTIVE, an upstream could potentially allow the original request to proceed **unchanged**; the separate ANLGarden relay may still reject VIP, and any real official authentication must still succeed. On CGM INACTIVE/expired/error, do not forward. No fake `error=false`, `session`, `data`, CCU, or Garden authorization is created.

## What the source does

- `phase40z_stage0_preflight.php` uses the existing `public/_secure_packet.php` to decode a protected **synthetic** request and check stage=`0`, packet length, two original login command bytes `0x01 0x01`, then extract **only** the first of three length-prefixed fields, verifying the `INPP.` email shape. Password and client-version bytes are skipped by field length and never returned, logged or displayed.
- A caller-injected lookup returns `state` and integer `vip_expiry`; only an unexpired `ACTIVE` at the supplied time returns `{decision:ACTIVE, forward_original:true}`. All other outcomes are fail closed. Does not change original login code or any upstream response.
- `phase40z_test.php` builds only fake client packets with `INPP.synthetic@example.invalid` and the literal `not-a-real-password`. Tests ACTIVE, plain identity preservation, privacy of decision output, INACTIVE, EXPIRED, errors, invalid frames/stage, unsupported legacy login, invalid packet checksum, empty payload, and expiry boundary. **15/15 passed** with the repository's original `_secure_packet.php` implementation.

Run in the staging-branch JPCG checkout:

```sh
php -l staging/phase40/phase40z_stage0_preflight.php
php staging/phase40/phase40z_test.php
```

## Important gaps before any real connection

1. This **is not integrated** into `public/session.php`, the Unity button, Render, or Nox. It proves parsing+membership decision with synthetic packets only.
2. A stage-0 envelope contains credential material protected by a compatibility transform, **not end-to-end encryption**. This parser decodes the request in memory to locate a claimed email. Never route live credentials to this staging parser without a security/privacy review and explicit user consent. Prefer a native email-only preflight if a reliable integration path becomes available.
3. Exact `INPP.` membership ID storage must match `codes.login_id` with verified ownership, not a guessed prefix alias. The claimed string alone is not an authenticated identity; the official password and provider/session checks cannot be skipped.
4. Stage-1 game traffic, reconnects and alternate login modes are **not** implemented here.
5. Current separately installed Nox 62032 APK has a different archive hash from the older Library staging APK; do not overwrite/install it from this report. The original ARM64 library hash matches the known mapping, but the current app integration remains unmodified.

**Next decision:** validate whether a privacy-preserving email-only native preflight is feasible in the existing binary, or whether an explicitly approved server-side preflight can be integrated into a new isolated staging endpoint without weakening external authorization. Never rewrite another provider's `VIP` rejection into success.
