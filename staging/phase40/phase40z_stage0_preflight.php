<?php
declare(strict_types=1);

/**
 * Offline/staging-only parser for the observed ANLGarden stage-0 relay envelope.
 * This does NOT authenticate a game account or produce valid session data.
 * It MUST NOT log, echo, persist, forward, or return password/packet contents.
 * Intended for synthetic fixtures and offline preflight feasibility research.
 */

function cgm40z_stage0_claimed_id(string $protected): string
{
    // Bound the work even before decoding; reject rather than truncate.
    if ($protected === '' || strlen($protected) > 16384) {
        throw new InvalidArgumentException('Invalid stage-0 request');
    }
    if (!function_exists('cgm_decrypt_packet')) {
        throw new LogicException('Missing existing _secure_packet.php dependency');
    }
    $envelope = cgm_decrypt_packet($protected);
    $parts = explode('~', $envelope);
    if (count($parts) !== 3 || $parts[1] !== '0') {
        throw new InvalidArgumentException('Not an initial stage-0 login');
    }
    $hex = $parts[0];
    if ($hex === '' || strlen($hex) > 8192 || (strlen($hex) % 2) !== 0 ||
        !ctype_xdigit($hex)) {
        throw new InvalidArgumentException('Invalid stage-0 frame');
    }
    $packet = hex2bin($hex);
    if ($packet === false || strlen($packet) < 12) {
        throw new InvalidArgumentException('Invalid stage-0 frame');
    }
    $len = strlen($packet);
    $size = unpack('v', substr($packet, 0, 2))[1];
    if ($size !== $len || ord($packet[6]) !== 1 || ord($packet[7]) !== 1) {
        throw new InvalidArgumentException('Unexpected login frame');
    }

    // The first of three length-prefixed fields is the account ID. The other
    // two are credential/client-version fields: skip by length, never return.
    $offset = 8;
    $identifier = '';
    for ($field = 0; $field < 3; $field++) {
        if ($offset >= $len) {
            throw new InvalidArgumentException('Incomplete login frame');
        }
        $fieldLen = ord($packet[$offset]);
        $offset++;
        if ($offset + $fieldLen > $len) {
            throw new InvalidArgumentException('Incomplete login field');
        }
        if ($field === 0) {
            $identifier = substr($packet, $offset, $fieldLen);
        }
        $offset += $fieldLen;
    }
    if ($offset !== $len || !str_starts_with($identifier, 'INPP.')) {
        // Other login modes (including legacy INFB.) are not in scope.
        throw new InvalidArgumentException('Unsupported login identity');
    }
    $email = substr($identifier, 5);
    if ($email === '' || strlen($email) > 254 ||
        filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Invalid claimed email');
    }
    return $identifier;
}

/**
 * Lookup callback receives an *unverified claimed ID* and timestamp, then
 * returns ['state'=>'ACTIVE'|'INACTIVE'|'EXPIRED'|'ERROR','vip_expiry'=>int].
 * Never interpret the result as game authentication, upstream VIP or a session.
 * Returns only a non-sensitive decision; no ID, packet, or password returned.
 */
function cgm40z_stage0_preflight(string $protected, callable $lookup, int $now): array
{
    try {
        $claimedId = cgm40z_stage0_claimed_id($protected);
        $result = $lookup($claimedId, $now);
        if (!is_array($result)) {
            return ['decision' => 'ERROR', 'forward_original' => false];
        }
        $state = $result['state'] ?? 'ERROR';
        $expiry = $result['vip_expiry'] ?? 0;
        if ($state === 'ACTIVE' && is_int($expiry) && $expiry > $now) {
            return ['decision' => 'ACTIVE', 'forward_original' => true];
        }
        if (in_array($state, ['INACTIVE', 'EXPIRED'], true) ||
            ($state === 'ACTIVE' && is_int($expiry) && $expiry <= $now)) {
            return ['decision' => 'INACTIVE', 'forward_original' => false];
        }
    } catch (Throwable $e) {
        // Never include exception messages, raw body or identifiers in output.
    }
    return ['decision' => 'ERROR', 'forward_original' => false];
}
