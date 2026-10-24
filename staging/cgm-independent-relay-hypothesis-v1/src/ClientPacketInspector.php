<?php
declare(strict_types=1);

/**
 * Offline read-only decoder of selected original CGM/ANLGarden 1026 requests.
 * Does not decrypt SecurePacket or authenticate a user. Input may contain
 * game credentials; diagnostic result never contains secret raw values.
 */
final class CgmClientPacketInspector
{
    private const MAX_PACKET_BYTES = 8192;

    /** @return array<string,int|string|bool> */
    public static function inspectEnvelope(string $decoded): array
    {
        if (strlen($decoded) > 32768) return self::invalid('oversized_envelope');
        $parts = explode('~', $decoded);
        if (count($parts) !== 3 || !in_array($parts[1], ['0', '1'], true)) {
            return self::invalid('invalid_envelope');
        }
        [$packetHex, $stageText, $session] = $parts;
        if ($packetHex === '' || strlen($packetHex) % 2 !== 0
            || strlen($packetHex) > 2 * self::MAX_PACKET_BYTES || !ctype_xdigit($packetHex)) {
            return self::invalid('invalid_packet_hex');
        }
        if (strlen($session) > 4096) return self::invalid('session_too_long');
        $stage = (int)$stageText;
        if (($stage === 0 && $session !== '') || ($stage === 1 && $session === '')) {
            return self::invalid('invalid_session_presence');
        }
        $packet = hex2bin($packetHex);
        if (!is_string($packet)) return self::invalid('invalid_packet_hex');
        return self::inspectBinary($packet, $stage, $session !== '');
    }

    /** @return array<string,int|string|bool> */
    public static function inspectBinary(string $packet, int $stage, bool $hasRelaySession): array
    {
        $size = strlen($packet);
        if ($size < 8 || $size > self::MAX_PACKET_BYTES) return self::invalid('invalid_packet_length');
        if (!in_array($stage, [0, 1], true)) return self::invalid('unsupported_stage');
        if (($stage === 0 && $hasRelaySession) || ($stage === 1 && !$hasRelaySession)) {
            return self::invalid('invalid_session_presence');
        }
        $declared = unpack('vlength', substr($packet, 0, 2));
        if (!is_array($declared) || $declared['length'] !== $size) {
            return self::invalid('length_mismatch');
        }
        // [LE u16 size][LE u32 sequence][u8 main][u8 sub]...
        $main = ord($packet[6]);
        $sub = ord($packet[7]);
        $base = [
            'kind' => 'UNRECOGNIZED',
            'stage' => $stage,
            'packet_bytes' => $size,
            'command_main' => $main,
            'command_sub' => $sub,
            'relay_session_present' => $hasRelaySession,
        ];
        if ($stage === 0 && $main === 1 && $sub === 1) {
            $cursor = 8;
            $lengths = [];
            $idNamespace = 'UNRECOGNIZED';
            for ($i = 0; $i < 3; $i++) {
                if ($cursor >= $size) return self::invalid('truncated_login_fields');
                $len = ord($packet[$cursor++]);
                if ($len === 0 || $cursor + $len > $size) {
                    return self::invalid('invalid_login_field_length');
                }
                if ($i === 0) {
                    $prefix = substr($packet, $cursor, min($len, 5));
                    $idNamespace = match ($prefix) {
                        'INPP.' => 'INPP',
                        'INFB.' => 'INFB',
                        default => 'UNRECOGNIZED',
                    };
                }
                $lengths[] = $len;
                $cursor += $len;
            }
            if ($cursor !== $size) return self::invalid('trailing_login_bytes');
            if ($idNamespace === 'UNRECOGNIZED') return self::invalid('unknown_login_namespace');
            return array_merge($base, [
                'kind' => 'LOGIN_SUBMIT',
                'login_namespace' => $idNamespace,
                'credential_fields_present' => count($lengths) === 3,
            ]);
        }
        if ($stage === 1 && (($main === 0 && $sub === 22) || ($main === 1 && $sub === 0))) {
            if ($size !== 16) return self::invalid('known_ccu_packet_wrong_length');
            return array_merge($base, [
                'kind' => ($main === 0) ? 'VERIFY_CCU' : 'GAME_LOGON',
                'has_two_dword_fields' => true,
            ]);
        }
        return $base;
    }

    /** @return array{kind:string,reason:string} */
    private static function invalid(string $reason): array
    {
        return ['kind' => 'INVALID', 'reason' => $reason];
    }
}
