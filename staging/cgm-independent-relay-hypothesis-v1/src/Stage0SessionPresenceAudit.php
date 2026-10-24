<?php
declare(strict_types=1);

/**
 * Offline, read-only structure audit for original 1026 Stage 0 envelopes.
 * Distinguishes an empty relay field from a nonempty, UNVERIFIED relay field.
 * Never authenticates a user, emits a reply, forwards packets, or trusts a token.
 * Input is already-decrypted SecurePacket plaintext; keep it private.
 */
final class CgmStage0SessionPresenceAudit
{
    private const MAX_ENVELOPE = 32768;
    private const MAX_FRAME = 8192;
    private const MAX_RELAY = 4096;

    /** @return array<string, bool|string|int> Redacted metadata only. */
    public static function inspect(string $decoded): array
    {
        if ($decoded === '' || strlen($decoded) > self::MAX_ENVELOPE) {
            return self::invalid('envelope_size');
        }
        $parts = explode('~', $decoded);
        if (count($parts) !== 3 || $parts[1] !== '0') {
            return self::invalid('not_stage0_envelope');
        }
        [$hex, , $relay] = $parts;
        if ($hex === '' || (strlen($hex) & 1) !== 0
            || strlen($hex) > self::MAX_FRAME * 2 || !ctype_xdigit($hex)) {
            return self::invalid('invalid_packet_hex');
        }
        if (strlen($relay) > self::MAX_RELAY || preg_match('/[\x00-\x1f\x7f]/', $relay)) {
            return self::invalid('invalid_relay_field');
        }
        $packet = hex2bin($hex);
        if (!is_string($packet) || strlen($packet) < 11) {
            return self::invalid('frame_size');
        }
        $frameLength = unpack('v', substr($packet, 0, 2));
        if (!is_array($frameLength) || $frameLength[1] !== strlen($packet)) {
            return self::invalid('frame_length_mismatch');
        }
        if (ord($packet[6]) !== 1 || ord($packet[7]) !== 1) {
            return self::invalid('not_stage0_login_command');
        }
        $cursor = 8;
        $namespace = '';
        for ($i = 0; $i < 3; $i++) {
            if ($cursor >= strlen($packet)) return self::invalid('truncated_safe_strings');
            $n = ord($packet[$cursor]);
            $cursor++;
            if ($n === 0 || $cursor + $n > strlen($packet)) {
                return self::invalid('invalid_safe_string_length');
            }
            if ($i === 0) {
                if ($n < 6) return self::invalid('invalid_login_namespace');
                $prefix = substr($packet, $cursor, 5);
                $namespace = match ($prefix) {
                    'INPP.' => 'INPP',
                    'INFB.' => 'INFB',
                    default => '',
                };
                if ($namespace === '') return self::invalid('invalid_login_namespace');
            }
            $cursor += $n;
        }
        if ($cursor !== strlen($packet)) return self::invalid('unexpected_frame_tail');
        $hasRelay = $relay !== '';
        return [
            'classification' => $hasRelay ? 'STAGE0_EXISTING_RELAY_UNTRUSTED' : 'STAGE0_NO_RELAY',
            'packet_bytes' => strlen($packet),
            'login_namespace' => $namespace,
            'relay_session_present' => $hasRelay,
            'claimed_login_only' => true,
            'structurally_recognized' => true,
            'authorized_provider_may_forward' => false,
            'relay_session_authenticated' => false,
            'official_identity_bound' => false,
            'game_authenticated' => false,
        ];
    }

    /** @return array<string, bool|string> */
    private static function invalid(string $why): array
    {
        return [
            'classification' => 'INVALID', 'reason' => $why,
            'structurally_recognized' => false,
            'authorized_provider_may_forward' => false,
            'relay_session_authenticated' => false,
            'official_identity_bound' => false,
            'game_authenticated' => false,
        ];
    }
}
