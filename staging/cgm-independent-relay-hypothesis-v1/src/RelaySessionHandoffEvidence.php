<?php
declare(strict_types=1);

/**
 * OFFLINE EVIDENCE ONLY. No network, storage, hashing for logging, token issuance,
 * authentication, packet forwarding or credential handling.
 *
 * Inputs are private relay-session values obtained lawfully from an authorized
 * source. Null means NOT OBSERVED; empty string means OBSERVED ABSENT.
 * This method returns ONLY allowlisted booleans and coarse classifications.
 * Never log the arguments or exception details.
 */
final class CgmRelaySessionHandoffEvidence
{
    /** @return array<string, bool|string|null> */
    public static function assess(
        ?string $stage0Incoming,
        ?string $stage0Response,
        ?string $firstStage1Request,
        ?bool $stage0ResponseError
    ): array {
        $base = [
            'response_shape_positive' => $stage0ResponseError === false,
            'stage0_incoming_present' => $stage0Incoming === null ? null : $stage0Incoming !== '',
            'stage0_response_present' => $stage0Response === null ? null : $stage0Response !== '',
            'stage1_request_present' => $firstStage1Request === null ? null : $firstStage1Request !== '',
            'response_matches_stage1' => null,
            'response_matches_incoming' => null,
            'session_issuer_verified' => false,
            'relay_session_authenticated' => false,
            'official_identity_bound' => false,
            'official_login_accepted' => false,
            'game_server_accepted' => false,
            'garden_server_accepted' => false,
            'may_forward' => false,
        ];
        $out = static fn(string $name, array $extra = []): array =>
            ['classification' => $name] + $extra + $base;
        // Do not let malformed or unbounded inputs leak into results.
        foreach ([$stage0Incoming, $stage0Response, $firstStage1Request] as $v) {
            if ($v !== null && (strlen($v) > 4096 || preg_match('/[\x00-\x1f\x7f]/', $v))) {
                return $out('INVALID_RELAY_WITNESS');
            }
        }
        if ($stage0ResponseError === null) return $out('RESPONSE_STATUS_UNOBSERVED');
        if ($stage0ResponseError === true) return $out('RESPONSE_ERROR_NO_HANDOFF');
        if ($stage0Response === null) return $out('RESPONSE_SESSION_UNOBSERVED');
        if ($stage0Response === '') return $out('RESPONSE_SESSION_ABSENT');
        if ($firstStage1Request === null) return $out('STAGE1_REQUEST_UNOBSERVED');
        if ($firstStage1Request === '') return $out('STAGE1_REQUEST_SESSION_ABSENT');

        $matchesStage1 = hash_equals($stage0Response, $firstStage1Request);
        $matchesIncoming = $stage0Incoming !== null && $stage0Incoming !== ''
            ? hash_equals($stage0Response, $stage0Incoming) : null;
        $extra = ['response_matches_stage1'=>$matchesStage1,
            'response_matches_incoming'=>$matchesIncoming];
        if (!$matchesStage1) return $out('RESPONSE_TO_STAGE1_MISMATCH', $extra);
        if ($stage0Incoming === null) return $out('HANDOFF_MATCH_PRIOR_UNKNOWN', $extra);
        if ($stage0Incoming === '') return $out('FRESH_REPLY_HANDOFF_MATCH', $extra);
        if ($matchesIncoming) return $out('EXISTING_VALUE_HANDOFF_MATCH', $extra);
        return $out('CHANGED_VALUE_HANDOFF_MATCH', $extra);
    }
}
