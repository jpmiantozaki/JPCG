# JPCG Web Admin Panel v1.3

Single-code generation UX update.

Changes:
- still generates exactly ONE JPANL code per click
- Generate button is disabled while the request is running to prevent accidental double-generation
- generated plaintext code is displayed more prominently
- Copy Code changes to "Copied!" after successful clipboard copy
- clear reminder that the plaintext should be copied now
- persistent database listing remains masked/hash-only
- no database or Android-client changes required

Replace only:
    public/admin_panel.html

Then redeploy Render.
