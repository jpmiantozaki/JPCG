# JPCG Render API — transport diagnostic

This package is intentionally stateless. Its purpose is to verify that a direct client
(curl / your controlled test client) can reach a PHP endpoint on Render without a browser cookie.

## Deploy

1. Create a GitHub repository (for example `jpcg-vip-test-api`).
2. Upload the contents of this ZIP to the repository root.
3. In Render: **New → Web Service** and connect the repository.
4. Choose **Docker** as the runtime and **Free** as the instance type.
5. Deploy. Render will assign an `https://<service>.onrender.com` URL.
6. Set the health check path to `/health.php` if Render does not pick it up from `render.yaml`.

No database is required for the first transport test.

## Test from Windows Command Prompt

Replace YOUR-SERVICE with the actual Render hostname:

    curl.exe -i "https://YOUR-SERVICE.onrender.com/health.php"

Expected body resembles:

    {"ok":true,"service":"jpcg-vip-test","time_utc":"..."}

Then:

    curl.exe -i "https://YOUR-SERVICE.onrender.com/redeemvip.php?data=TEST-NOT-A-REAL-CODE"

Expected:

    {"ok":false,"state":"invalid_coupon","code":"TEST-NOT-A-REAL-CODE"}

For a controlled JPANL-format transport test:

    curl.exe -i "https://YOUR-SERVICE.onrender.com/redeemvip.php?data=JPANL123456789"

Expected state: `active`.

## Important

Render Free web services can spin down after inactivity. The first request after a sleep can
take substantially longer while the service starts.

Render Free web-service files are ephemeral. This package therefore does NOT use `vip.json`
or write redemption state to disk. After transport compatibility is proven, use a persistent
datastore for real entitlement state rather than local JSON files.

Keep normal login/authentication endpoints separate from this test API.
