<?php
declare(strict_types=1);

/**
 * Offline read-only classifier for directly observed Unity 1026 stage-1
 * client packet senders. Does NOT send a packet, issue a relay token,
 * authenticate a game user, or claim that any server accepts the frame.
 */
final class CgmStage1CoverageInspector
{
    /** @return array<string,int|string|bool> Redacted metadata only. */
    public static function inspect(string $packet): array
    {
        $size = strlen($packet);
        if ($size < 8 || $size > 8192) return self::invalid('frame_size');
        $declared = unpack('vsize', substr($packet, 0, 2));
        if (!is_array($declared) || $declared['size'] !== $size) {
            return self::invalid('length_mismatch');
        }
        $main = ord($packet[6]);
        $sub = ord($packet[7]);
        $bodySize = $size - 8;
        $name = 'UNRECOGNIZED';
        $shapeOK = false;
        $rva = '';
        if ($main === 0 && $sub === 0) {
            $name = 'PING'; $shapeOK = $bodySize === 0; $rva = '0x69B8C8';
        } elseif ($main === 0 && $sub === 22) {
            $name = 'VERIFY_CCU'; $shapeOK = $bodySize === 8; $rva = '0x69A504';
        } elseif ($main === 1 && $sub === 0) {
            $name = 'GAME_LOGON'; $shapeOK = $bodySize === 8; $rva = '0x69B9D8';
        } elseif ($main === 9 && $sub === 0) {
            $name = 'REQUEST_ENTER_CHANNEL'; $shapeOK = $bodySize === 1; $rva = '0x69BBA4';
        } elseif ($main === 10 && $sub === 0) {
            $name = 'REQUEST_USER_INFO'; $shapeOK = $bodySize === 0; $rva = '0x69BCC8';
        } elseif ($main === 31 && $sub === 12) {
            $name = 'REQUEST_COUPLE_LEVEL'; $rva = '0x69BDD8';
            // The native writer calls Packet.WriteSafeString: one u8 length,
            // followed by exactly that many bytes. Do not expose contents.
            $shapeOK = $bodySize >= 2 && ord($packet[8]) > 0
                && ord($packet[8]) + 1 === $bodySize;
        }
        if ($name === 'UNRECOGNIZED') {
            return ['kind'=>'UNRECOGNIZED','packet_bytes'=>$size,
                'command_main'=>$main,'command_sub'=>$sub,
                'game_authenticated'=>false];
        }
        if (!$shapeOK) return self::invalid('known_command_invalid_shape');
        return ['kind'=>$name, 'source_rva'=>$rva, 'packet_bytes'=>$size,
            'command_main'=>$main, 'command_sub'=>$sub,
            'game_authenticated'=>false, 'relay_session_authenticated'=>false,
            'packet_accepted_by_server'=>false];
    }

    private static function invalid(string $why): array
    {
        return ['kind'=>'INVALID','reason'=>$why,'game_authenticated'=>false];
    }
}
