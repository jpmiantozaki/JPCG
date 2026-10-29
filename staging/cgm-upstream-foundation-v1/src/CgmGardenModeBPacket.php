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
 * Native x86 0x009DE2C3 / 0x009DE1C4 prove a single selected table byte
 * is reused for EVERY payload byte. This fully decodes large Garden packets.
 * Transport decoding is NOT game authentication or Garden authorization.
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
     *               decoded_prefix:string,decoded_prefix_bytes:int,
     *               decoded_payload:string,payload_bytes:int,complete:bool}
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
        // One packet-scoped key byte: native PE32 disassembly confirms the
        // lookup address is constant for the ENTIRE native body loop.
        $key = ord($this->bodyTable[$bodySelector]);
        $decodedPayload = '';
        for ($i = 0; $i < $payloadLength; $i++) {
            $decodedPayload .= chr(ord($wire[4 + $i]) ^ $key);
        }
        $complete = true;
        return [
            'status' => 'FULLY_DECODED',
            'original_length' => $originalLength,
            'wire_length' => strlen($wire),
            'decoded_prefix' => $decodedPayload, // backward-compatible alias
            'decoded_prefix_bytes' => $payloadLength,
            'decoded_payload' => $decodedPayload,
            'payload_bytes' => $payloadLength,
            'complete' => $complete,
        ];
    }
}
