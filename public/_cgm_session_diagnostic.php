<?php
declare(strict_types=1);

require_once __DIR__ . '/_secure_packet.php';

// Read-only inspection of RelayWebManager.SendRelayInfo's outer envelope.
// The binary game packet is deliberately not treated as an authenticated ID.
function cgm_session_request_summary(string $protected): array
{
    if ($protected === '' || strlen($protected) > 65536) {
        return ['format' => 'unavailable', 'reason' => 'missing_or_oversized'];
    }
    try {
        $raw = cgm_decrypt_packet($protected);
    } catch (Throwable $e) {
        return ['format' => 'unavailable', 'reason' => 'invalid_protected_packet'];
    }
    $parts = explode('~', $raw);
    if (count($parts) !== 3 || $parts[0] === ''
        || strlen($parts[0]) % 2 !== 0 || !ctype_xdigit($parts[0])
        || !preg_match('/^(0|[1-9][0-9]{0,2})$/D', $parts[1])
        || (int)$parts[1] > 255) {
        return ['format' => 'unrecognized', 'reason' => 'unexpected_envelope'];
    }
    return [
        'format' => 'protected_binary_stage_session',
        'stage' => (int)$parts[1],
        'packet_bytes' => intdiv(strlen($parts[0]), 2),
        'relay_session_present' => $parts[2] !== '',
        'membership_lookup' => 'not_performed',
        'reason' => 'account_field_not_established',
    ];
}

function cgm_log_session_request(): void
{
    try {
        // Observe GET/form POST only. Do not consume the raw relay request body.
        $value = $_GET['data'] ?? $_POST['data'] ?? '';
        $protected = is_string($value) ? $value : '';
        $record = cgm_session_request_summary($protected);
        error_log('[CGM_SESSION_REQUEST] ' . json_encode($record, JSON_UNESCAPED_SLASHES));
    } catch (Throwable $e) {
        error_log('[CGM_SESSION_REQUEST] {"format":"unavailable","reason":"diagnostic_failure"}');
    }
}
