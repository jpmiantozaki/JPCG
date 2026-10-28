# CGM Phase 2.9A — Verified original account identity normalization

Date: 2026-10-28. **OFFLINE COMPLETE ONLY**; no native hook or genuine game login.

## Newly resolved blockers

- Original CGM APK native `Login()` retrieves `InputField.text`, then calls `System.String.Replace(" ", "")` at `0x6A87FC`. Exact literal indexes `98` (space), `0` (empty) decoded from metadata version 31 and hash-locked ARM64 GOT. The prepared value is passed in original `SendLogin(x1)` at `0x6A8868`.
- Original `SendLogin()` creates the account namespace internally through `System.String.Concat`: ordinary account `INPP.` + prepared username; legacy web account `INFB.` + prepared username. Literal indexes `2681`/`2679` and original caller flag selection verified.
- The CGM membership email must correspond to **prepared** `SendLogin(x1)` identity (unprefixed). Gate must re-read current original field, check the original unchanged x1, bind a request generation and input-edit revision, and atomically consume fresh ACTIVE exactly once. No case folding or broad whitespace trimming is justified by these original instructions.
- This mapping still does not prove that an email membership claimant owns the authenticated PlayPark account.

## Artifacts and checks

- `tools/verify_identity_chain.py` hash-locked read-only metadata/GOT and callsite decoder; exact signed CGM reference verified.
- `policy/cgm29a_identity_bind.c/.h`: no auth packet manipulation; original Phase40BR guard and Phase29 synthetic sender preserved.
- **53/53** new C native-identity model assertions, **18/18** new Python tests, AddressSanitizer/UBSan PASS.
- Full source ZIP `CGM_Phase29A_Original_Login_Identity_Chain_2026-10-28.zip` delivered in this conversation; ZIP SHA-256 `8be478bc375ae6b394a9bb27959988f57530f8b7046cfc4059d0376fd7ff6db4`. The repository checkpoint is documentation; no original native binary/credentials/packets embedded.

## Phase 2.9B — Actual original APK handoff target

Use existing Phase40BP Java/JNI Unity owner-thread integration and bounded Phase40BV MethodInfo guest-code witness. Before a live account login, establish **verified** native ABI-compatible conditional interception and original submit-route coverage under isolated synthetic fixtures. `Login()` denial must skip both SendLogin and `isLoginProcess=1`; ACTIVE may allow **exactly one untouched** original send only for the same prepared username and a fresh authenticated membership decision.

The original ordinary username preparation and provider prefixing are now known, but that does **not** prove account ownership. Independently verify CGM membership binding to authenticated PlayPark identity and genuine official LoginServer/CCU/GameServer/GardenServer acceptance.

No native patch, APK install, Nox/Frida attachment, traffic replay, PHP endpoint changes, or PlayPark login occurred. JPCG production, Render/Neon and Nox remain unchanged; rollback `62030` never accessed.
