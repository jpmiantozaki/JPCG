# JPCG account-status backend patch

Copy `public/account_status.php` into the existing JPCG v2 repository's `public/` directory and redeploy Render.

It adds:

    GET /account_status.php?login=<login identifier>

Response examples:

    {"ok":true,"state":"active","vip_expiry":1793150381}
    {"ok":true,"state":"expired","vip_expiry":...}
    {"ok":true,"state":"not_activated","vip_expiry":null}

The endpoint does not return JPANL codes, sessions, or relay-data tokens.

After deployment, test in a browser or PowerShell:
    https://jpcg.onrender.com/account_status.php?login=JPANDROID1
