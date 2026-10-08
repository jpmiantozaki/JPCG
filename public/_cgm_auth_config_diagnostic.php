<?php
declare(strict_types=1);

require_once __DIR__ . '/_secure_packet.php';

function cgm_auth_config_summary(string $body): array
{
    $record = ['diagnostic' => 'cgm-auth-config-v2'];
    if ($body === '' || strlen($body) > 65536) {
        return $record + ['format' => 'unavailable', 'reason' => 'missing_or_oversized'];
    }

    try {
        $payload = json_decode(cgm_decrypt_packet(trim($body)), true, 32, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return $record + ['format' => 'unavailable', 'reason' => 'invalid_protected_json'];
    }

    if (!is_array($payload) || array_is_list($payload)) {
        return $record + ['format' => 'unrecognized'];
    }

    $record['format'] = 'protected-json';
    $record['error'] = is_bool($payload['error'] ?? null) ? $payload['error'] : null;

    // Allowlisted connection details only; never log other response fields.
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

    $field = 'GM_LS_SERVER_VERSION';
    $present = array_key_exists($field, $payload);
    $version = $present ? $payload[$field] : null;
    $type = $present ? get_debug_type($version) : 'missing';
    $record['version_present'] = $present;
    $record['version_type'] = $type;

    // Accept bounded non-sensitive scalar version identifiers; suppress anything else.
    $safe = false;
    if (is_int($version) || is_float($version)) {
        $candidate = (string)$version;
        $safe = is_finite((float)$version) && strlen($candidate) <= 64
            && preg_match('/^[A-Za-z0-9._+:-]+$/D', $candidate) === 1;
    } elseif (is_string($version)) {
        $candidate = $version;
        $safe = strlen($candidate) <= 64
            && preg_match('/^[A-Za-z0-9._+:-]+$/D', $candidate) === 1;
    }
    $record['GM_LS_SERVER_VERSION'] = $safe ? $candidate : null;
    $record['version_withheld'] = $present && $version !== null && !$safe;

    return $record;
}

function cgm_log_auth_config(string $body, int $status): void
{
    $record = cgm_auth_config_summary($body);
    $record['http_status'] = $status;
    error_log('[CGM_AUTH_CONFIG] ' . json_encode($record, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
}
