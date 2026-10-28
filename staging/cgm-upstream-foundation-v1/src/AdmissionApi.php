<?php
declare(strict_types=1);
namespace CGM\UpstreamV4;

require_once __DIR__ . '/ProviderHandoffGate.php';

use CGM\Phase40CD\ProviderHandoffGate;

/**
 * A server-side provider admission policy route, NOT the legacy session.php.
 * An ACTIVE membership assertion sent by the client is never accepted.
 * No official game password, protected packet, CCU, or Garden tokens are used.
 */
final class AdmissionApi
{
    private const SCHEMA = 'cgm-authorized-upstream-admission-v1';
    public function __construct(private ProviderHandoffGate $gate) {}

    /** @return array{http_status:int,payload:array<string,bool|string>} */
    public function handle(string $method, string $contentType, string $body, int $now): array
    {
        if (strtoupper($method) !== 'POST') return self::deny(405, 'METHOD_NOT_ALLOWED');
        if (strtolower(trim(explode(';', $contentType)[0])) !== 'application/json') {
            return self::deny(415, 'JSON_REQUIRED');
        }
        if ($body === '' || strlen($body) > 8192) return self::deny(400, 'BODY_SIZE');
        try {
            $data = json_decode($body, true, 5, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) { return self::deny(400, 'INVALID_JSON'); }
        if (!is_array($data) || array_is_list($data)) return self::deny(400, 'INVALID_JSON_ROOT');
        $expected = ['attempt_id', 'claimed_email', 'provider_proof', 'schema'];
        $actual = array_keys($data);
        sort($actual);
        if ($actual !== $expected) return self::deny(400, 'INVALID_FIELDS');
        // Reject duplicate serialized root keys even though json_decode uses
        // last-write-wins. Overly conservative rejection is always fail-closed.
        if (preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"\s*:/s', $body, $matches) !== 4) {
            return self::deny(400, 'DUPLICATE_OR_NESTED_FIELDS');
        }
        $names = [];
        foreach ($matches[1] as $rawKey) {
            try { $decodedKey = json_decode('"' . $rawKey . '"', true, 2, JSON_THROW_ON_ERROR); }
            catch (\Throwable $e) { return self::deny(400, 'INVALID_FIELDS'); }
            $names[] = $decodedKey;
        }
        sort($names);
        if ($names !== $expected) return self::deny(400, 'DUPLICATE_OR_NESTED_FIELDS');
        if ($data['schema'] !== self::SCHEMA ||
            !is_string($data['attempt_id']) || !is_string($data['claimed_email']) ||
            !is_string($data['provider_proof'])) return self::deny(400, 'INVALID_FIELDS');
        // Underlying trusted gate validates attempt, expiry, exact link and provider.
        $decision = $this->gate->evaluate(
            $data['claimed_email'], $data['provider_proof'], $data['attempt_id'], $now
        );
        $attested = ($decision['decision'] ?? null) === 'PRECONDITIONS_ATTESTED_ONLY';
        $reason = (string)($decision['reason'] ?? 'VERIFICATION_UNAVAILABLE');
        return ['http_status' => $attested ? 200 :
            (in_array($reason, ['PROVIDER_UNCONFIGURED_OR_UNVERIFIED', 'VERIFICATION_UNAVAILABLE'], true) ? 503 : 403),
            'payload' => [
                'scope' => 'CGM_UPSTREAM_ADMISSION_POLICY_ONLY',
                'decision' => $attested ? 'PRECONDITIONS_ATTESTED_ONLY' : 'DENIED',
                'reason' => $reason,
                'provider_session_created' => false,
                'game_packet_generated' => false,
                'session_issued' => false,
                'garden_authorized_by_cgm' => false,
            ]];
    }

    /** @return array{http_status:int,payload:array<string,bool|string>} */
    private static function deny(int $status, string $reason): array
    {
        return ['http_status' => $status, 'payload' => [
            'scope' => 'CGM_UPSTREAM_ADMISSION_POLICY_ONLY',
            'decision' => 'DENIED', 'reason' => $reason,
            'provider_session_created' => false,
            'game_packet_generated' => false,
            'session_issued' => false,
            'garden_authorized_by_cgm' => false,
        ]];
    }
}
