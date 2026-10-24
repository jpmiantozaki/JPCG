# CGM-owned 1026 mobile relay — zero-transform feasibility hypothesis

**Status: offline-only research, NOT a working or deployable session.php.**

## What is newly established

The Unity client builds a full framed request and sends its hex through the protected HTTP mobile relay. A positive-shaped `MGRelayResponse.data` is converted back into bytes and sent to the chosen LoginServer or GameServer socket according to `servertype`. The native callback does not itself treat that response as successful official game authentication.

We tested one **unproven** interpretation: returning the **original outgoing framed packet unchanged** inside the original encrypted mobile relay response. Our independent CGM membership check admits only the exact case-preserved prefixed identity to this offline candidate. This removes BASE/MOD VIP from the *simulated CGM relay decision*, but says nothing about PlayPark accepting the packet.

The hypothesis is intentionally conservative: historical evidence hints at packet sizes differing between client requests and relay-produced data, so BASE/MOD could be applying a necessary transformation. We have **not** established that byte-for-byte forwarding is sufficient to reach the Garden.

## Source committed

- `src/ZeroTransformRelayCandidate.php`: no HTTP/network capability; explicitly blocks non-CLI use; original protected request and CGM exact ACTIVE membership preflight; encrypted mobile-relay response candidate; random short-lived *mobile-only* relay token, simulated Stage 1 request continuity.
- `src/ClientPacketInspector.php`: existing original Unity packet format classifier, now committed as an actual dependency.

The candidate produces a *hypothetical relay admission* (`error:false`) for an eligible Stage 0 request only as an offline fixture. It does NOT claim legitimate game login, account ownership, server-issued CCU, accepted session or Garden access. An inactive member receives CGM's native-shaped `VIP` denial. No passwords, IDs, raw packets or tokens are included in diagnostic metadata; protected response and in-memory state still require secret-safe handling.

## Results in local assistant environment

**38/38 offline checks PASS**, including byte-identity for Stage 0 and both mapped Stage 1 requests, exact ID matching, expired/missing token rejection, CGM VIP denial, negative cases, and diagnostics redaction.

Previous unchanged offline tests also still pass: request classifier **34/34**, relay response validator **75/75**, and Stage 0 provider gate **66/66**. These are synthetic and are not successful official logins.

The full 38-case test script was executed locally, but was **not committed with this checkpoint**. No user Windows or Nox test is necessary for this stage.

## Do NOT deploy

This candidate has no official network interface, a non-durable in-memory token map, and no authenticated identity binding. It is not the original legacy `session.php`; the production `public/session.php` has not been changed. No third-party server was contacted.

A future step requires permission for a controlled original-service interoperability check or legitimately documented examples of packets actually accepted by the game servers. At that point, determine whether native packet forwarding is accepted byte-for-byte or needs an authorized transformation. A successful-looking HTTP envelope must never be mistaken for PlayPark login acceptance.
## 2026-10-24 update: Stage 1 gap demonstrated, zero-transform remains NO-GO

Direct disassembly now identifies **six** Stage 1 Unity game senders, not just the two supported by this hypothetical relay. The newly mapped command `09 00` (`SendRequestEnterChannel`) is particularly important because the authentic GameServer channel reply handler can trigger GardenServer connection. This staging candidate blocks it; even if Stage 0 worked, this incomplete implementation cannot reproduce the normal Garden entry path. Read the [complete Stage 1 native audit](ORIGINAL_UNITY_STAGE1_GARDEN_COVERAGE_AUDIT_2026-10-24.md).

The original BASE-origin eligible record also showed a roughly 58-byte Stage 0 request and roughly 63-byte returned data packet. Hence *unconditional request-to-response byte identity* does not describe that original observed BASE behavior. No universal transformation can be inferred from the length difference.

New **actual** source: [`Stage1CoverageInspector.php`](src/Stage1CoverageInspector.php), a read-only classifier, plus [39 offline checks](tests/stage1_coverage.php). The prototype's simulated CCU state is now named `ccu_request_seen` rather than `verified`; sending a CCU request is not an official GameServer acceptance event. Existing 38-case local regression still passes.

**Do not deploy or connect this candidate to any live original game socket.** Its apparent positive response is deliberately synthetic, its relay token exists only in offline simulated memory, and no authorized, accepted original game session has been demonstrated.
