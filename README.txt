JPCG VIP Service v1
===================

Independent JPANL entitlement backend. It does NOT authenticate against
official ANLGarden/PlayPark relay or game servers.

Features
--------
- Unique JPANL + 9 digit codes
- SHA-256 code storage (plaintext codes returned only when generated)
- One-time redemption
- Binding to a login identifier
- Server-side VIP expiry
- 15-minute sessions
- Relay-data rotation on verify
- LoginServer(0) -> GameServer(1) mock transition
- Admin-protected generator/listing
- SQLite persistent database path: /var/data/jpcg.sqlite

IMPORTANT RENDER SETUP
----------------------
For persistence, attach a Render persistent disk mounted at:

    /var/data

Without a persistent disk, the SQLite database can disappear when the
instance is replaced/redeployed.

Set this Render environment variable:

    JPCG_ADMIN_KEY = <your own strong secret>

A random suggested key for initial setup is shown below. Change it if desired:
    o0mJif8RkvqWoz2u0vhkARnkGxZoLQpOf3xVyuptSr8

Do NOT commit the real admin key into GitHub.

Endpoints
---------
GET  /health.php

POST /admin_generate.php
Header: X-Admin-Key: <secret>
JSON: {"count":5,"days":30}

GET /admin_codes.php
Header: X-Admin-Key: <secret>

POST /redeemvip.php
JSON: {"login":"jpcg-test","code":"JPANL123456789"}

POST /verify.php
JSON: {"session":"...","data":"..."}

GET /status.php?session=...

PowerShell generator example
----------------------------
$headers = @{ "X-Admin-Key" = "YOUR_SECRET" }
$body = @{ count=5; days=30 } | ConvertTo-Json
Invoke-RestMethod -Method Post `
  -Uri "https://YOUR-SERVICE.onrender.com/admin_generate.php" `
  -Headers $headers -ContentType "application/json" -Body $body

Security
--------
Never send/store the user's password. Bind entitlements only to the chosen
login identifier or, later, an internal account ID.
