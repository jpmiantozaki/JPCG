<?php
declare(strict_types=1);

require_once __DIR__ . '/phase40z_stage0_preflight.php';

/**
 * NON-DEPLOYABLE fixture router. Accepts exactly one impossible-to-own fixture ID.
 * No HTTP listener, database access, network call, or production route is here.
 *
 * The downstream adapter is an injected local callback. Its opaque request bytes
 * and returned {status, headers, body} are NEVER transformed on ACTIVE.
 * This does not create a game session or override other providers' VIP checks.
 */
function cgm40aa_route_synthetic(
    string $protectedRequest,
    callable $membershipLookup,
    callable $downstreamFixture,
    int $now
): array {
    if (getenv('CGM_PHASE40AA_SYNTHETIC_ONLY') !== '1') {
        return ['route' => 'BLOCKED', 'reason' => 'TEST_MODE_DISABLED'];
    }
    try {
        $claimedId = cgm40z_stage0_claimed_id($protectedRequest);
        if (!hash_equals('INPP.synthetic@example.invalid', $claimedId)) {
            return ['route' => 'BLOCKED', 'reason' => 'NON_FIXTURE_ID'];
        }
    } catch (Throwable $e) {
        return ['route' => 'BLOCKED', 'reason' => 'INVALID_FRAME'];
    }

    $decision = cgm40z_stage0_preflight($protectedRequest, $membershipLookup, $now);
    if (($decision['forward_original'] ?? false) !== true) {
        return [
            'route' => 'BLOCKED',
            'reason' => ($decision['decision'] ?? 'ERROR') === 'INACTIVE'
                ? 'CGM_INACTIVE' : 'CGM_CHECK_ERROR',
        ];
    }

    try {
        // No new URL, decrypted credentials, reconstructed packet, or forged VIP result.
        $response = $downstreamFixture($protectedRequest);
        if (!is_array($response) || !isset($response['status'], $response['headers'], $response['body']) ||
            !is_int($response['status']) || $response['status'] < 100 || $response['status'] > 599 ||
            !is_array($response['headers']) || !is_string($response['body'])) {
            return ['route' => 'BLOCKED', 'reason' => 'DOWNSTREAM_ERROR'];
        }
        return ['route' => 'DOWNSTREAM', 'provider_response' => $response];
    } catch (Throwable $e) {
        return ['route' => 'BLOCKED', 'reason' => 'DOWNSTREAM_ERROR'];
    }
}
