# CGM Phase 2.8.5 — Hardened source-only login precheck

**Not an installable APK patch. No official game login or Garden acceptance is claimed.**

## What works at source level
- Posts only `schema`, `request_id`, `email` to the existing HTTPS `https://jpcg.onrender.com/authfirst.php`.
- Requires a matching, unexpired LIVE `ACTIVE` response before the original callback would be eligible.
- Rejects wrong request IDs, INACTIVE/ERROR, clock/expiry/TTL violations and network failures. Cancels stale attempts after editing/cancel/panel hide.
- Retains the original `LoginServerManager.Login()` as a distinct Unity event and **does not invoke it by default**. Future release requires BOTH inspector opt-in and `CGM_AUTHORIZED_ORIGINAL_LOGIN_SOURCE` compile define.
- Checks for replacement of the original inner CONFIRM `onClick` event at startup and during use; disables normal interaction on a mismatch. This is defense-in-depth, NOT a guarantee against unverified programmatic `Login()` callers.

## Compatibility finding
The previously recovered offline v4 `LoginServerManager.Start()` assigns new `Button.ButtonClickedEvent` instances to the original buttons. Its `Login()` is an offline stub. Consequently the current AssetRipper Unity export is **not** a supported destination for an effective real-login gate; the Editor installer must not be treated as sufficient.

## Test locally
Requires .NET 8:
```powershell
dotnet run --project .\staging\cgm-authfirst-source-gate-v2\tests\csharp\PolicyRuntimeTests.csproj --configuration Release
```
36 deterministic tests compile the actual `Assets/CGMAuthFirst/CgmMembershipPolicy.cs` file and execute its decision code, without Unity, network or game credentials. A separate Unity Editor source test file contains the prior 18 synthetic cases, not run in CI.

The complete Windows bundle, readiness check, and 28 static safety assertions are in the downloadable Phase 2.8.5 archive attached to the chat. **Do not add the authorization compile define to the AssetRipper export.**

## Remaining milestones
1. Verify normal event ownership and any dynamic/programmatic submission routes in an authorized source/extension.
2. Bind CGM email membership to the identity actually authenticated by PlayPark. Email-only ACTIVE is insufficient proof.
3. Independently verify genuine login, CCU, GameServer and GardenServer acceptance; known ports are not an authorized session processor.

No edits to production JPCG PHP, databases, APKs, or NoxPlayer were made by this branch. Rollback Nox `62030` remains untouched.
