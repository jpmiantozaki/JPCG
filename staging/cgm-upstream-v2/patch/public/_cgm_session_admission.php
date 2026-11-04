<?php
declare(strict_types=1);

require_once __DIR__ . '/_cgm_stage0_vip_gate.php';

/**
 * A staging-only, fail-closed membership admission decision.
 * This function never validates official credentials or creates game sessions.
 * The returned array contains no identifier, password, packet, or session data.
 *
 * @return array{action:string,reason:string,reply?:array}
 */
function cgm_session_admission(
    string $protected,
    callable $decrypt,
    callable $lookup,
    int $now,
    bool $gateEnabled,
    string $sessionMode,
    bool $originExplicitlyConfigured
): array {
    if (!$gateEnabled) {
        return ['action' => 'LEGACY_ROUTE', 'reason' => 'GATE_DISABLED'];
    }

    $decision = cgm_stage0_vip_decision($protected, $decrypt, $lookup, $now);
    if ($decision['decision'] !== 'FORWARD_UNCHANGED') {
        return [
            'action' => 'REJECT',
            'reason' => $decision['reason'],
            'reply' => cgm_stage0_rejection($decision),
        ];
    }

    // This explicit mode is only compatibility with the pre-existing relay,
    // NOT a claim that the origin accepts CGM VIP as independent entitlement.
    if ($sessionMode === 'existing_origin' && $originExplicitlyConfigured) {
        return ['action' => 'FORWARD_UNCHANGED', 'reason' => 'CGM_ACTIVE'];
    }

    // Until a real, compatible provider processor is supplied, never respond
    // error=false, manufacture session/data, or pretend authentication worked.
    return [
        'action' => 'REJECT',
        'reason' => 'SESSION_PROCESSOR_UNAVAILABLE',
        'reply' => cgm_stage0_rejection(['reason' => 'LOOKUP_UNAVAILABLE']),
    ];
}
