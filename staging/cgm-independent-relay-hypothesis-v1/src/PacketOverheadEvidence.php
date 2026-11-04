<?php
declare(strict_types=1);

/**
 * Offline, redacted metadata analysis only.
 *
 * IMPORTANT: do not feed HTTP URLs, SecurePacket text, credential data,
 * packet hex, relay session content, or client/device IDs into this class.
 * Accept only the allowlisted structured metadata schema.
 * Length consistency does NOT prove authentication or packet encryption.
 */
final class CgmPacketOverheadEvidence
{
    /** @param array<int,mixed> $records
     *  @return array<string,mixed>
     */
    public static function assess(array $records): array
    {
        $observations = [];
        $rejected = 0;
        $expectedKeys = [
            'request_stage', 'request_bytes', 'response_request_stage',
            'response_request_bytes', 'response_bytes', 'response_error',
            'http_status', 'origin', 'relay_session_bytes',
        ];
        sort($expectedKeys);
        foreach ($records as $record) {
            if (!is_array($record)) { $rejected++; continue; }
            $keys = array_keys($record);
            sort($keys);
            if ($keys !== $expectedKeys
                || !is_int($record['request_stage'])
                || !in_array($record['request_stage'], [0, 1], true)
                || !is_int($record['response_request_stage'])
                || $record['response_request_stage'] !== $record['request_stage']
                || !is_int($record['request_bytes']) || $record['request_bytes'] < 8
                || $record['request_bytes'] > 8192
                || !is_int($record['response_request_bytes'])
                || $record['request_bytes'] !== $record['response_request_bytes']
                || !is_int($record['response_bytes']) || $record['response_bytes'] < 1
                || $record['response_bytes'] > 8192
                || $record['response_error'] !== false
                || $record['http_status'] !== 200
                || $record['origin'] !== 'anlgarden.com'
                || !is_int($record['relay_session_bytes'])
                || $record['relay_session_bytes'] !== 12) {
                $rejected++;
                continue;
            }
            $delta = $record['response_bytes'] - $record['request_bytes'];
            $compatible = $delta >= 3 && $delta <= 12;
            $observations[] = [
                'stage' => $record['request_stage'],
                'input_bytes' => $record['request_bytes'],
                'output_bytes' => $record['response_bytes'],
                'delta_bytes' => $delta,
                'consistent_with_native_inbound_overhead_range' => $compatible,
                // Arithmetic only: NOT a decoded header, key or server cipher.
                'hypothetical_k' => $compatible ? $delta - 3 : null,
            ];
        }
        $allInRange = $observations !== [] && count(array_filter($observations,
            static fn(array $x): bool => $x['consistent_with_native_inbound_overhead_range'])) === count($observations);
        $deltas = array_column($observations, 'delta_bytes');
        return [
            'schema' => 'cgm-redacted-length-evidence-v1',
            'sample_count' => count($observations),
            'rejected_records' => $rejected,
            'distinct_deltas' => count(array_unique($deltas)),
            'all_consistent_with_3_to_12' => $allInRange,
            'evidence_scope' => 'one historical BASE origin session; summary lengths only',
            'transform_algorithm_proven' => false,
            'official_authentication_proven' => false,
            'output_packets_reconstructable' => false,
            'observations' => $observations,
        ];
    }
}
