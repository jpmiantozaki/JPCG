# Couple Garden Mobile – Redeem Diagnostic

This is a temporary, non-authenticating diagnostic endpoint.

It records only:
- request method/path
- query length
- whether `data` exists
- `data` length
- a broad character-set classification
- SHA-256 of the received `data` value
- user-agent/content metadata

It deliberately does NOT:
- decrypt the protected payload
- log the full protected payload
- validate CGMANG codes
- issue VIP/session/data credentials
- connect to an official relay

## Safe deployment

1. Upload `public/redeemvip_diagnostic.php` to the existing CGM backend.
2. Do NOT replace the working `redeemvip.php` yet.
3. First verify in a browser:
   `/redeemvip_diagnostic.php?data=TEST123`
   It should return HTTP 400 with `CGM diagnostic capture complete`.
4. For the APK experiment, route only the test APK's redemption URL to:
   `/redeemvip_diagnostic.php?data=`
   while leaving authenticate/session/verify and all other endpoints unchanged.
5. Enter a disposable test input in the APK redemption field.
6. In Render logs, look for:
   `[CGM_REDEEM_DIAGNOSTIC]`
7. Repeat with a second disposable input and compare:
   `data_length`, `data_charset`, and `data_sha256`.

After the experiment, restore the test APK redemption URL. The endpoint can then be removed.

Do not paste full protected request payloads or secrets into public screenshots.
