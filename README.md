# JPCG CGMANG backend patch

New code format:
    CGMANG#########

Changes:
- admin_generate.php now generates CGMANG + 9 digits only.
- redeemvip.php accepts CGMANG + 9 digits.
- Backward compatibility is retained for already-issued JPANL + 9 digit codes.
- Existing database records do not need migration.
- Existing active memberships are unaffected.

Deploy:
Replace only:
    public/admin_generate.php
    public/redeemvip.php

Do not replace _common.php or the database.
