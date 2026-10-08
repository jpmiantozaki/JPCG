<?php
declare(strict_types=1);
require_once __DIR__ . '/_secure_packet.php';

function cgm_auth_config_summary(string $body): array
{
    $record = ['diagnostic' => 'cgm-auth-config-v1'];
    if ($body === '' || strlen($body) > 65536) {
        return $record + ['format' => 'unavailable', 'reason' => 'missing_or_oversized'];
    }
    try {
        $payload = json_decode(cgm_decrypt_packet(trim($body)), true, 32, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return $record + ['format' => 'unavailable', 'reason' => 'invalid_protected_json'];
    }
    if (!is_array($payload)) return $record + ['format' => 'unrecognized'];
    $record['format'] = 'protected-json';
    $record['error'] = is_bool($payload['error'] ?? null) ? $payload['error'] : null;
    // Exact allowlist: no message, credentials, packet data, or session values.
    foreach (['GM_RELAY_SERVER_IP', 'GM_LS_SERVER_IP'] as $field) {
        $value = $payload[$field] ?? null;
        $valid = is_string($value) && strlen($value) <= 253
            && (filter_var($value, FILTER_VALIDATE_IP) !== false
                || filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false);
        $record[$field] = $valid ? $value : null;
    }
    foreach (['GM_RELAY_SERVER_PORT', 'GM_LS_SERVER_PORT', 'GM_GS_SERVER_PORT'] as $field) {
        $value = $payload[$field] ?? null;
        $record[$field] = is_int($value) && $value >= 1 && $value <= 65535 ? $value : null;
    }
    $version = $payload['GM_LS_SERVER_VERSION'] ?? null;
    $record['GM_LS_SERVER_VERSION'] = is_string($version) && strlen($version) <= 32
        && preg_match('/^[0-9]+(?:\.[0-9]+)*$/D', $version) ? $version : null;
    return $record;
}

function cgm_log_auth_config(string $body, int $status): void
{
    $record = cgm_auth_config_summary($body);
    $record['http_status'] = $status;
    error_log('[CGM_AUTH_CONFIG] ' . json_encode($record, JSON_UNESCAPED_SLASHES));
}
