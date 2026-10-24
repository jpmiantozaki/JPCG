<?php
declare(strict_types=1);

/**
 * Staging-only header reduction for the pre-existing credential-bearing relay.
 * Does not parse, modify, send, log, authenticate, or issue protected packets.
 * Never deploy without authorization and a compatible upstream's requirements.
 */
final class CgmRelayForwardHeaderPolicy
{
    /** @return list<string> Only headers needed for generic HTTP transport. */
    public static function select(array $server, string $method): array
    {
        $method = strtoupper($method);
        if (!in_array($method, ['GET', 'HEAD', 'POST'], true)) {
            throw new InvalidArgumentException('Unsupported method');
        }
        $result = ['Accept-Encoding: identity', 'Connection: close'];
        $allowed = [
            'HTTP_USER_AGENT' => ['User-Agent', 512],
            'HTTP_ACCEPT' => ['Accept', 512],
        ];
        if ($method === 'POST') {
            $allowed['CONTENT_TYPE'] = ['Content-Type', 256];
        }
        foreach ($allowed as $key => [$name, $maxBytes]) {
            $value = $server[$key] ?? null;
            if (!is_string($value) || $value === '' || strlen($value) > $maxBytes) {
                continue;
            }
            // Reject any controls, including CR/LF, as well as non-ASCII bytes.
            if (preg_match('/[^\x20-\x7e]/D', $value)) {
                continue;
            }
            $result[] = $name . ': ' . $value;
        }
        return $result;
    }

    /** @return array<string,bool|int|string> Safe metadata only. */
    public static function audit(array $server, string $method): array
    {
        $headers = self::select($server, $method);
        return [
            'classification' => 'STAGING_HEADER_REDUCTION_ONLY',
            'header_count' => count($headers),
            'protected_request_preserved_by_existing_bridge' => true,
            'provider_permission_verified' => false,
            'official_identity_authenticated' => false,
            'game_session_authorized' => false,
            'live_forwarding_enabled' => false,
        ];
    }
}
