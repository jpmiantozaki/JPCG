# Corrected JPCG account-status patch

This version matches the deployed v2 schema:
- table: `codes`
- bound account column: `login_id`
- expiry column: `vip_expiry`
- redemption marker: `redeemed_at`

Replace the previous `public/account_status.php` with this file and redeploy.

Then test:
https://jpcg.onrender.com/account_status.php?login=JPANDROID1

Expected for the existing active entitlement:
{"ok":true,"state":"active","vip_expiry":1793150381}
