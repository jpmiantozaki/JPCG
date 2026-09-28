# JPCG Web Admin Panel v1

Browser UI for the existing independent JPANL backend.

Features:
- admin-key login
- generate JPANL entitlements
- list up to the records returned by existing admin_codes.php
- filter unused / active / expired
- local date/time formatting
- account hashes only; no raw login IDs displayed
- admin key stored only in browser sessionStorage, not hard-coded into the page

## Install
Copy:
    public/admin_panel.html
into the existing backend repository's `public/` directory and redeploy.

Then visit:
    https://jpcg.onrender.com/admin_panel.html

The page calls the existing:
- /admin_generate.php
- /admin_codes.php

IMPORTANT: This UI assumes admin_generate.php accepts JSON `{ "vip_days": 30 }`, consistent with the current JPCG service design. If your deployed generator uses a different parameter name/shape, the Generate button may need a one-line adjustment; listing/authentication will still work.

Do not commit your JPCG_ADMIN_KEY into this file or GitHub.
