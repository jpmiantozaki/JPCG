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

// Only recognized, generic static errors may be printed verbatim. An arbitrary
// upstream message can contain an account/password/token and is never logged.
function cgm_session_error_message_summary(mixed $message): array
{
    if (!is_string($message)) {
        return ['message_present' => false, 'message_category' => 'unavailable'];
    }
    $normalized = strtolower(trim((string)preg_replace('/\s+/', ' ', $message)));
    $known = [
        // Exact static marker checked by the unmodified session callback.
        'vip' => 'upstream_vip_marker',
        'invalid username or password' => 'credentials_rejected',
        'invalid username or password.' => 'credentials_rejected',
        'incorrect username or password' => 'credentials_rejected',
        'incorrect username or password.' => 'credentials_rejected',
        'wrong username or password' => 'credentials_rejected',
        'invalid credentials' => 'credentials_rejected',
        'invalid credentials.' => 'credentials_rejected',
        'login failed' => 'login_rejected',
        'login failed.' => 'login_rejected',
        'authentication failed' => 'login_rejected',
        'user not found' => 'account_not_found',
        'account not found' => 'account_not_found',
        'account banned' => 'account_restricted',
        'account suspended' => 'account_restricted',
        'account disabled' => 'account_restricted',
        'vip required' => 'membership_required',
        'membership required' => 'membership_required',
        'vip expired' => 'membership_expired',
        'membership expired' => 'membership_expired',
        'invalid session' => 'session_rejected',
        'session expired' => 'session_rejected',
        'server maintenance' => 'maintenance',
        'server unavailable' => 'upstream_unavailable',
        'invalid request' => 'request_rejected',
        'invalid packet' => 'request_rejected',
    ];
    $record = ['message_present' => $message !== '', 'message_bytes' => strlen($message)];
    if (isset($known[$normalized])) {
        // Canonical allowlisted text, never arbitrary upstream text.
        $record['message_text'] = $normalized;
        $record['message_category'] = $known[$normalized];
        $record['message_withheld'] = false;
    } else {
        $record['message_category'] = $message === '' ? 'empty' : 'unrecognized';
        $record['message_withheld'] = $message !== '';
    }
    return $record;
}

function cgm_session_response_summary(string $body): array
{
    if ($body === '' || strlen($body) > 65536) {
        return ['format' => 'unavailable', 'reason' => 'missing_or_oversized'];
    }
    try {
        $payload = json_decode(cgm_decrypt_packet(trim($body)), false, 32, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return ['format' => 'unavailable', 'reason' => 'invalid_protected_json'];
    }
    if (!is_object($payload)) {
        return ['format' => 'unrecognized', 'reason' => 'unexpected_json_shape'];
    }
    $error = $payload->error ?? null;
    $record = [
        'format' => 'protected-json',
        'error' => is_bool($error) ? $error : null,
        'error_type' => get_debug_type($error),
        'data_present' => isset($payload->data) && is_string($payload->data) && $payload->data !== '',
        'session_present' => isset($payload->session) && is_string($payload->session) && $payload->session !== '',
    ];
    // Success messages and unrelated response fields never enter the log.
    if ($error === true) {
        $record += cgm_session_error_message_summary($payload->message ?? null);
    }
    return $record;
}

function cgm_log_session_response(string $body, int $status, string $upstreamHost): void
{
    try {
        $record = [
            'diagnostic' => 'cgm-session-response-v2',
            'path' => '/session.php',
            'status' => $status,
            'upstream_host' => $upstreamHost,
            'body_bytes' => strlen($body),
        ] + cgm_session_response_summary($body);
        error_log('[CGM_SESSION_RESPONSE] ' . json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    } catch (Throwable $e) {
        error_log('[CGM_SESSION_RESPONSE] {"format":"unavailable","reason":"diagnostic_failure"}');
    }
}
