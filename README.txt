JPCG Mock VIP Protocol
======================

Purpose
-------
Independent test protocol for JPANL codes. It does NOT connect to, generate
credentials for, or authenticate against the official game's relay servers.

Recovered client-shaped response fields used by this mock:
  error       bool
  message     string
  data        string (our own random mock relay token)
  servertype  int    (0 = mock LoginServer, 1 = mock GameServer)
  session     string (our own short-lived random session)
  vip_expiry  uint32 Unix seconds

Flow
----
1) POST /redeemvip.php
   JSON: {"login":"test@example.invalid","code":"JPANL123456789"}

   Returns servertype=0 plus our own data/session tokens.

2) POST /verify.php
   JSON: {"session":"<session from step 1>","data":"<data from step 1>"}

   Returns servertype=1 and rotates the mock relay data token.

3) GET /status.php?session=<session>
   Shows the current mock stage and expiries.

Deployment
----------
Upload this repository to a NEW Render service. Do not replace the current
diagnostic service until you have tested this mock independently.

Important: Render free instances have ephemeral local storage. sessions.json
is therefore suitable only for this diagnostic. A production design should
use a persistent database.

Local test examples
-------------------
curl -s -X POST http://localhost/redeemvip.php   -H "Content-Type: application/json"   -d '{"login":"test@example.invalid","code":"JPANL123456789"}'

Then pass the returned session and data values to /verify.php.

Security notes
--------------
- Never send the user's password to this service.
- Bind production entitlements to a stable login identifier or internal ID.
- Store redemption codes hashed in a persistent database.
- Use one-time redemption and server-side expiry checks.
- Keep the mock relay tokens separate from all official service credentials.
