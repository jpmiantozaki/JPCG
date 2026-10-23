<?php
declare(strict_types=1);

/**
 * STAGING-CANDIDATE REPLACEMENT for JPCG public/session.php.
 * This file is NOT automatically installed or deployed by the test package.
 *
 * With CGM_STAGE0_GATE_ENABLED != 1, legacy session routing is unchanged.
 * With it == 1, only an authenticated SecurePacket stage-0 envelope containing
 * a syntactically valid INPP. login identifier can request CGM membership.
 * Password and full protected request MUST NOT be logged or mutated.
 */
if (getenv('CGM_STAGE0_GATE_ENABLED') === '1') {
    require_once __DIR__ . '/_common.php';
    require_once __DIR__ . '/_secure_packet.php';
    require_once __DIR__ . '/_cgm_membership.php';
    require_once __DIR__ . '/_cgm_session_admission.php';

    // This staging gate is strictly stage-0 and GET only. Follow-on relay
    // stages need provider-issued session-state binding and are not supported.
    $protected = null;
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        $item = $_GET['data'] ?? null;
        if (is_string($item)) $protected = $item;
    }
    $protected ??= '';

    $now = time();
    $sessionMode = (string)(getenv('CGM_UPSTREAM_SESSION_MODE') ?: 'unconfigured');
    $origin = (string)(getenv('CGM_ORIGINAL_ORIGIN') ?: '');
    $decision = cgm_session_admission(
        $protected,
        static fn(string $packet): string => cgm_decrypt_packet($packet),
        static fn(string $prefixedId): array => cgm_membership_lookup(db(), $prefixedId, $now),
        $now,
        true,
        $sessionMode,
        $origin !== ''
    );

    // Bounded status-only logging; no URL/query, credentials, names or packets.
    error_log('[CGM_ADMISSION] action=' . $decision['action'] . ' reason=' . $decision['reason']);
    if ($decision['action'] !== 'FORWARD_UNCHANGED') {
        cgm_secure_respond($decision['reply'] ?? cgm_stage0_rejection(['reason'=>'LOOKUP_UNAVAILABLE']), 200);
    }
    // Forward only through pre-existing code. No decryption/re-encoding of the
    // original request is passed to the next handler; it sees the original GET.
}

// Existing original JPCG code path. With the flag off, this behaves exactly
// like the pre-existing public/session.php.
if (getenv('CGM_SESSION_DIAGNOSTICS') === '1') {
    require_once __DIR__ . '/_cgm_session_diagnostic.php';
    cgm_log_session_request();
}
require __DIR__ . '/_cgm_session_bridge.php';
cgm_session_bridge('session.php');
