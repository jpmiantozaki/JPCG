Couple Garden Mobile Admin v1.7 — Account/Login display

Deploy:
1. Replace your current admin_panel.html/assets with the included admin public files.
2. Replace public/admin_codes.php with backend/public/admin_codes.php.

No database migration is needed. Redeemed codes already store login_id.
Unused codes will have no account/login value.

The authenticated admin endpoint will now return the login identifier instead of a SHA-256 hash.
Keep JPCG_ADMIN_KEY private.
