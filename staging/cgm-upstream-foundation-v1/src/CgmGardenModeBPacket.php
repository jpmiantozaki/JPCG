<?php
declare(strict_types=1);
namespace CGM\Phase33X;

/**
 * Bounded, pure Garden transport frame decoder for original Audition PC
 * alternative mode (captured port 20840). NO credentials or sessions.
 *
 * Caller supplies the exact 256-byte and 512-byte lookup tables extracted
 * offline from their own verified EXE. Original proprietary tables are NOT
 * included in this source. This class never connects to any game server.
 *
 * The body transform can read beyond the initialized 256-byte table in the
 * original binary. This adapter returns a prefix-only result in that case;
 * callers MUST NOT treat the prefix as a complete authenticated packet.
 */
final class CgmGardenModeBPacket
{
    public function __construct(
        private readonly string $bodyTable,
        private readonly string $headerTable,
    ) {
        if (strlen($bodyTable) !== 256 || strlen($headerTable) !== 512) {
            throw new \InvalidArgumentException('Exact mode-B tables are required');
        }
    }

    /** Original two-byte packet length recovered from 3 encoded bytes. */
    public function originalLength(string $head): int
    {
        if (strlen($head) !== 3) throw new \InvalidArgumentException('Invalid header size');
        $selector = ord($head[2]);
        $lo = ord($head[0]) ^ ord($this->headerTable[$selector * 2]);
        $hi = ord($head[1]) ^ ord($this->headerTable[$selector * 2 + 1]);
        return $lo | ($hi << 8);
    }

    /**
     * @return array{status:string,original_length:int,wire_length:int,
     *               decoded_prefix:string,decoded_prefix_bytes:int,complete:bool}
     */
    public function decodeFrame(string $wire): array
    {
        if (strlen($wire) < 5) throw new \InvalidArgumentException('Frame too short');
        $originalLength = $this->originalLength(substr($wire, 0, 3));
        // This mode adds 3 encoded-header bytes + 1 body-selector byte,
        // replacing the original two length bytes: wire=plain+2.
        if ($originalLength < 3 || strlen($wire) !== $originalLength + 2) {
            throw new \InvalidArgumentException('Mode-B frame length mismatch');
        }
        $bodySelector = ord($wire[3]);
        $payloadLength = $originalLength - 2;
        $available = max(0, 256 - $bodySelector);
        $decodedCount = min($payloadLength, $available);
        $decodedPrefix = '';
        for ($i = 0; $i < $decodedCount; $i++) {
            $decodedPrefix .= chr(ord($wire[4 + $i]) ^ ord($this->bodyTable[$bodySelector + $i]));
        }
        $complete = $decodedCount === $payloadLength;
        return [
            'status' => $complete ? 'FULLY_DECODED' : 'PREFIX_ONLY_UNVERIFIED_LONG_BODY',
            'original_length' => $originalLength,
            'wire_length' => strlen($wire),
            'decoded_prefix' => $decodedPrefix,
            'decoded_prefix_bytes' => $decodedCount,
            'complete' => $complete,
        ];
    }
}
