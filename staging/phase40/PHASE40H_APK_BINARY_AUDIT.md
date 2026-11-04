# Phase 40H — Experimental CGM APK packaged-gate audit

Date: 2026-10-20. Binary analyzed **offline only**. No Nox or backend access.

## Inspected package

- Supplied artifact: `CGM-62031-Local-SIGNED.apk`.
- SHA-256: `deb51bfb4c81e55efbb7a1909458895312251f61b999b92e141133f91811b3e0`.
- ZIP entries inspected in-memory, without installation or binary modification:
  `classes.dex` (2,997,228 bytes),
  `assets/bin/Data/Managed/Metadata/global-metadata.dat` (4,906,768 bytes),
  `lib/arm64-v8a/libil2cpp.so` (27,093,976 bytes).

## Exact byte-string checks

| Exact ASCII literal | classes.dex | Unity metadata | ARM64 libil2cpp.so |
|---|---:|---:|---:|
| `authfirst.php` | 0 | 0 | 0 |
| `EmailMembershipGate` | 0 | 0 | 0 |
| `UnityEmailGate` | 0 | 0 | 0 |
| `CgmEmailLoginGate` | 0 | 0 | 0 |
| `HttpsEmailTransport` | 0 | 0 | 0 |
| `jpcg.onrender.com` | 0 | 0 | 0 |
| `wtn.pages.dev` | 0 | 0 | 0 |
| `127.0.0.1:8091` | 0 | 5 | 0 |
| `/verify.php` | 0 | 1 | 0 |
| `/session.php` | 0 | 1 | 0 |

These are **exact-byte presence checks**, not comprehensive bytecode
analysis. Absence cannot prove the class or gate does not exist under a
different name, resource encoding, dynamic assembly, or generated URL.
Likewise, the localhost references demonstrate packaged metadata strings
but do not establish which endpoint was actually called at runtime.

## Consequence for Phase 40

The previous 59 passing tests validate PHP staging components, not a
compiled Auth-First hook in the original Android APK. There is no binary
evidence in this check that `LoginServerManager.Login()` or
`LoginServerManager.SendLogin()` waits for CGM ACTIVE before proceeding.

Next actual implementation acceptance criterion: in an approved experimental
test environment, instrument observable call order only (no credentials or
packet dumps) to prove `Login()` is held while a CGM eligibility result is
pending; INACTIVE/EXPIRED/ERROR abort the login attempt; ACTIVE calls the
**unchanged** official login with the same account identifier. Existing
serialized `btnLogin` inner and outer paths must both be covered.

**Separate blocker:** even a successful client-side gate does not remove
the existing third-party `/session.php` data-provider dependency or
establish a PlayPark-authorized independent relay.

Production PHP routes, gateway, APK content, official PC EXE and both Nox
instances remain unchanged by this audit.
