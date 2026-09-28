CGM APK Experiment Backend Alias

Upload:
    public/r.php

The experiment APK routes ONLY the original redemption URL to:
    https://jpcg.onrender.com/r.php?data=

All other original endpoints remain unchanged.

r.php is the same non-authenticating diagnostic logger used previously:
it returns HTTP 400 intentionally and issues no VIP/session/relay credentials.
