# CGM relay privacy hardening — Apache access logs (staging only)

**Checkpoint:** 24 October 2026. **Scope:** privacy mitigation for the current JPCG gateway; NOT a compatible game-session processor or CGM-only login implementation.

## Why this matters

The original Unity 1026 client sends the protected Stage 0 login envelope in a GET query parameter to `/session.php?data=...`. The existing SecurePacket protection is a reversible XOR/checksum format and the framed login packet contains the official account identifier and password fields. October 8 user-owned historical Apache access logs demonstrably contain complete request query strings. The current production `Dockerfile` uses `php:8.3-apache` and copies `public/` without an explicit access-log privacy override; `public/_cgm_session_bridge.php` logs its own redacted metadata but does not override Apache's access logging.

**A PHP metadata diagnostic is not an Apache access-log privacy control.** The base Debian Apache default site uses `CustomLog ... combined` (includes `%r`, the full request line), and its `other-vhosts-access-log.conf` supplies another combined-format logger in the Debian Apache configuration studied. Simply adding a second sanitized CustomLog would leave the first unsafe one in place.

## Staging-only remediation

- `apache-cgm-vhost.conf`: replacement for the image's default virtual host. Its only explicit access log uses `LogFormat "%t \"%m %U %H\" %>s %B" cgm_path_only`. `%U` is the request path **without the query string**; the format omits remote IP, Referer, user-agent, Cookie, Authorization, `%r` and `%q`.
- `Dockerfile.staging`: builds a separate opt-in image from the repo root, retains the production PHP dependencies and `public/` app, copies the staging vhost config over `000-default.conf`, removes Debian's `other-vhosts-access-log.conf` symlink and checks Apache syntax. It does **not** modify the production Dockerfile or other application routes.
- `tests/config_audit.php`: nine offline static assertions against the staging configuration.

## Actual checks performed

1. **PHP 8.4 static config checks:** `ALL 9/9 PASS`.
2. **PHP syntax:** `No syntax errors detected` for the new config-audit test.
3. **Apache 2.4.68 locally:** `Syntax OK` with the copied staging vhost included.
4. **Local HTTP smoke request:** a disposable Apache instance bound to `127.0.0.1:18777` served a static `index.html` and returned HTTP 200. The request had fictional `data` and `token` query markers. The actual Apache access log retained a normal path-only entry: `"GET /index.html HTTP/1.1" 200 2` and contained **neither** fictional query marker.
5. **Not tested:** no Docker image build was run (Docker CLI unavailable), no Render or Cloudflare deployment, no external upstream request, no real credential, no modified APK/Nox or official game server.

## Use only in a controlled environment

From the repository root, once a Docker build environment is available:

```sh
php staging/cgm-relay-privacy-v1/tests/config_audit.php
docker build -f staging/cgm-relay-privacy-v1/Dockerfile.staging -t jpcg:relay-privacy-test .
```

This is not a request to deploy. Do not switch live routing merely because this local smoke test passed. When auditing a future permitted test deployment, verify **every** logging layer, including reverse-proxy, platform ingress, container access/error logs, APM/tracing, upstream, and query-bearing request URLs. Also review `public/_cgm_session_bridge.php` forwarding `Authorization`, `Cookie` and incoming headers to the configured external origin, and ensure actual third-party permission and trust boundaries before sending official credentials.

This work **does not remove credentials from the GET request itself** and cannot erase historic logs or prevent an upstream proxy from capturing the query. A comprehensive mitigation would require an authorized revised transport/client/proxy contract. Avoid promising end-to-end secrecy based solely on the Apache configuration.

## Remaining game architecture block

`public/session.php` still delegates to the old upstream. CGM ACTIVE proves only CGM membership. Independently accepted official game sessions require a legitimately authorized, compatible game-bound packet processor, authenticated player identity, server-issued relay state and official LoginServer/GameServer/GardenServer acceptance. This privacy work is independent of those unsolved requirements.

**Branch-only:** `feat/phase40-staging-provider-interface`. No production `Dockerfile`, `public/session.php`, default branch, emulator, APK, or live server was modified.

Apache documentation: https://httpd.apache.org/docs/2.4/mod/mod_log_config.html.en
