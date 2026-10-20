# CGM / JPCG Backend

CGM membership and redemption backend for the experimental CGM Android APK. **This backend does not create or authorize PlayPark game sessions.**

## Active deployment source
- Render `Dockerfile` copies **`public/`** (not the historical root PHP wrappers or `backend/`).
- Cloudflare Pages uses `cloudflare-pages/functions/[[path]].js` and `cloudflare-pages/public/_routes.json`.
- `public/authfirst.php`, `public/verify.php`: independent CGM ACTIVE/expiry checks.
- `public/redeemvip.php`: CGM CODE redemption.
- `public/authenticate.php`, `public/session.php`: still call `public/_cgm_session_bridge.php`. The bridge defaults to `https://anlgarden.com` when `CGM_ORIGINAL_ORIGIN` is not supplied. **These routes are not BASE/MOD independent yet.**

## Local preflight
Run `php -l` on each PHP file and `python3 -m unittest discover -s tests -v` before proposing an APK-related deployment. These checks validate source consistency, not official game login.

## Sensitive configuration
Set `DATABASE_URL` and `JPCG_ADMIN_KEY` through Render's secret environment variables. Admin calls use the `X-Admin-Key` header (never query-string `admin_key`). Do not commit signing material, passwords, token captures or session packets.

## Release/testing limitations
- Do not replace the external bridge with dummy `error=false` sessions or assume CGM ACTIVE means a PlayPark session is authorized.
- Obtain a legitimate provider-compatible transport and confirm no BASE/MOD dependency before claiming independent login/Garden access.
- Do not route real credential-bearing `session.php?data=...` requests into staging or request-body/access logs.
- Preserve the signed baseline and test only experimental Nox `127.0.0.1:62031`; **never touch rollback `127.0.0.1:62030`**.
- Treat historical root-level PHP code and `backend/` as archival until callers have been reviewed. They have not been deleted in this cleanup.

Health `/health.php` checks database availability; the JSON API root `/` is informational and does not prove membership or session acceptance.
