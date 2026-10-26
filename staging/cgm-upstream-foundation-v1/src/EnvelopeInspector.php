<?php
declare(strict_types=1);
namespace CGM\Phase40CC;

/**
 * Read-only structural check of the original client's packetHex~stage~relaySession
 * envelope. It neither authenticates accounts nor produces game-bound packets.
 * Do not log packet hex, relay tokens, decrypted text, or packet counter values.
 */
final class EnvelopeInspector
{
    private const MAX_PROTECTED_BYTES = 65536;
    private const MAX_DECODED_BYTES = 32768;
    private const MAX_PACKET_BYTES = 8192;
    private const COMMANDS = [
        '0016' => 'VERIFY_CCU',
        '0100' => 'GAME_LOGON',
        '0a00' => 'USER_INFO',
        '1f0c' => 'COUPLE_LEVEL',
        '0900' => 'ENTER_CHANNEL',
        '0000' => 'PING',
    ];

    /** @return array<string,int|string|bool> */
    private static function invalid(string $reason): array
    {
        return [
            'classification' => 'INVALID', 'reason' => $reason,
            'official_account_authenticated' => false,
            'game_session_authorized' => false,
        ];
    }

    /**
     * The decryptor must be a trusted *local* callable. Input may contain a
     * reversible protected password: never expose it in output or logging.
     * @param callable(string):string $decrypt
     * @return array<string,int|string|bool>
     */
    public static function inspectProtected(string $protected, callable $decrypt): array
    {
        if ($protected === '' || strlen($protected) > self::MAX_PROTECTED_BYTES) {
            return self::invalid('PROTECTED_INPUT_SIZE');
        }
        try {
            $decoded = $decrypt($protected);
            if (!is_string($decoded)) return self::invalid('DECODER_RESULT');
        } catch (\Throwable $e) {
            return self::invalid('DECRYPTION_FAILURE');
        }
        return self::inspectDecoded($decoded);
    }

    /** @return array<string,int|string|bool> */
    public static function inspectDecoded(string $decoded): array
    {
        if ($decoded === '' || strlen($decoded) > self::MAX_DECODED_BYTES) {
            return self::invalid('DECODED_INPUT_SIZE');
        }
        $parts = explode('~', $decoded);
        if (count($parts) !== 3) return self::invalid('ENVELOPE_FIELDS');
        [$hex, $stageText, $relayState] = $parts;
        if ($stageText !== '0' && $stageText !== '1') {
            return self::invalid('STAGE_NOT_SUPPORTED');
        }
        $stage = (int)$stageText;
        if ($hex === '' || strlen($hex) % 2 !== 0 || !ctype_xdigit($hex)) {
            return self::invalid('PACKET_HEX');
        }
        $packetLength = intdiv(strlen($hex), 2);
        if ($packetLength < 8 || $packetLength > self::MAX_PACKET_BYTES) {
            return self::invalid('PACKET_SIZE');
        }
        if (strlen($relayState) > 256) return self::invalid('RELAY_STATE_SIZE');
        // Archived observations include nonempty stage-0 relay state. Do NOT
        // impose a false requirement that a stage-0 token must be empty.
        if ($stage === 1 && $relayState === '') return self::invalid('STAGE1_RELAY_STATE_ABSENT');
        $packet = hex2bin($hex);
        if ($packet === false) return self::invalid('PACKET_HEX');
        $declared = unpack('vlength', substr($packet, 0, 2));
        if (!is_array($declared) || $declared['length'] !== $packetLength) {
            return self::invalid('FRAME_LENGTH_MISMATCH');
        }
        // Bytes 2..5 are the incrementing per-Session packet counter, but its
        // value is neither logged nor used as a unique request identifier.
        $command = bin2hex(substr($packet, 6, 2));
        if ($stage === 0) {
            if ($command !== '0101') return self::invalid('UNMAPPED_STAGE0_COMMAND');
            $commandName = 'LOGIN_PACKET_SHAPE';
        } else {
            $commandName = self::COMMANDS[$command] ?? null;
            if ($commandName === null) return self::invalid('UNMAPPED_STAGE1_COMMAND');
        }
        return [
            'classification' => 'CLIENT_FRAME_SHAPE_ONLY',
            'stage' => $stage,
            'command' => $commandName,
            'packet_bytes' => $packetLength,
            'relay_state_present' => $relayState !== '',
            'official_account_authenticated' => false,
            'game_session_authorized' => false,
        ];
    }
}
