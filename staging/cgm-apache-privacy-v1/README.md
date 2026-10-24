# CGM transport privacy — staging only (2026-10-24)

The unchanged original 1026 client uses a credential-bearing protected GET query `/session.php?data=...`. JPCG's own PHP diagnostics already avoid logging that value, but `php:8.3-apache`'s default `combined` access log includes `%r`, which can log the full query string. The current root Dockerfile did not override that format.

**Staging change:** substitute only the Apache default virtual host's access-log format, using `%U` for the URL path, with no `%r`, `%q`, Referer or headers. The sample `Dockerfile.staging` continues to copy the unchanged `public/` directory. It is **not** the deployed Dockerfile.

**Local-only validation performed:** Apache 2.4 config syntax PASS; temporary localhost Apache served synthetic `GET /session.php?data=CGM_TEST...` successfully; its access log contained `path=/session.php` but no marker or `data=` query. No real credentials or third-party services were touched. This validates Apache behavior in the test environment, not the exact production image or ingress.

**Limitations:** Render ingress, Cloudflare, other proxies, and the permitted original upstream may separately record URLs. This Apache configuration does not remove those logs or protect query data against a service that can decrypt it. Legacy GET remains unchanged; do not change the APK contract, stage0/1 replies, or remote VIP decisions. The previous Identity Trust Boundary v1 already tests a separate safe-header helper; do not duplicate that work.

**No production deployment, no APK change, and no independent session processor introduced.** Offline test shell, fuller implementation notes, and ZIP are in the project conversation artifact `CGM_Apache_Privacy_Check_v1.zip`.

Suggested *staging-only* image build from repo root: `docker build -f staging/cgm-apache-privacy-v1/Dockerfile.staging -t cgm-apache-privacy-staging .`
