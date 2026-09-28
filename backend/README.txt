JPCG VIP Service v2 - PostgreSQL
================================
Independent JPANL test entitlement service.

Purpose
-------
Moves JPCG entitlement/session storage from ephemeral SQLite to PostgreSQL while preserving the tested API contract:
- admin_generate.php
- admin_codes.php
- redeemvip.php
- verify.php
- status.php
- health.php

Required Render environment variables
-------------------------------------
DATABASE_URL     = Render PostgreSQL Internal Database URL
JPCG_ADMIN_KEY   = your existing admin key

Deployment
----------
1. Replace the existing repository files with this package's files.
2. Keep DATABASE_URL and JPCG_ADMIN_KEY configured in Render.
3. Deploy.
4. Open https://jpcg.onrender.com/health.php
Expected fields include:
  "service":"jpcg-vip-service-v2"
  "database":"postgresql"
  "db_ok":true

The first DB connection automatically creates the codes/sessions tables.

After health succeeds, generate a NEW JPANL test code. The previous SQLite code is not migrated because its database was lost.

Security
--------
Do not commit DATABASE_URL, database passwords, or JPCG_ADMIN_KEY to GitHub.
Delete admin_diag.php if it is still present in the repository.
