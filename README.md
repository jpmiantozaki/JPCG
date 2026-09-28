# JPCG Web Admin Panel v1.2

Corrected to the deployed `admin_generate.php` contract:

Request:
    POST /admin_generate.php
    {"days":30,"count":1}

Response:
    {"ok":true,"days":30,"codes":["JPANL#########"]}

New:
- displays the full newly generated plaintext code
- Copy Code button
- still uses X-Admin-Key authentication
- refreshes the code table after generation

Replace only `public/admin_panel.html` and redeploy.
