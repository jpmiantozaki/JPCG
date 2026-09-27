JPCG Verification-Only Diagnostic
=================================

Purpose
-------
This package is for the verification-only ANLGarden test APK.
Normal login, session, redemption and analytics endpoints remain unchanged
in the APK. Only verify.php is redirected to the controlled Render service.

Deploy
------
Replace/update the files in the existing Render GitHub repository with
the contents of this folder, commit, and let Render redeploy.

Test
----
1. Confirm https://jpcg-vip-test-api.onrender.com/health.php still returns ok.
2. Install ANLGarden_JPCG_VERIFY_ONLY_TEST.apk.
3. Log in normally.
4. Observe what happens at the VIP verification step.
5. Open Render Logs and copy lines beginning:
      [JPCG_VERIFY]
      [JPCG_VERIFY_RESPONSE]
   Do not share passwords. Authorization/Cookie headers are redacted.

Important
---------
This is a diagnostic response, not a reproduction of any official VIP
service. It does not generate or validate official codes.
