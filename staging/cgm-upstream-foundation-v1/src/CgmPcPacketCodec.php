<?php
declare(strict_types=1);
namespace CGM\Phase33W;

/**
 * Original Windows Audition PC transport scrambling layer, staging only.
 * Assembly: encoder 0x009E0740 via 0x009E0EF0; decoder 0x009E0580
 * via 0x009E0E80. Structural capture audit: 229/230 complete candidates.
 * This does NOT issue game or Garden sessions, or authenticate any account.
 */
final class CgmPcPacketCodec
{
    private const MAX_PLAINTEXT_BYTES = 51200;
    private static function rol8(int $n, int $s): int {
        $n &= 255;
        return (($n << $s) | ($n >> (8 - $s))) & 255;
    }
    private static function ror8(int $n, int $s): int {
        $n &= 255;
        return (($n >> $s) | ($n << (8 - $s))) & 255;
    }

    /** Fixture key is for deterministic host-only testing. */
    public static function scramble(string $plain, ?string $fixtureKey = null): string {
        $n = strlen($plain);
        if ($n > self::MAX_PLAINTEXT_BYTES) throw new \InvalidArgumentException('Packet too large');
        $k = $fixtureKey === null ? random_int(2, 9) : strlen($fixtureKey);
        if ($k < 2 || $k > 9) throw new \InvalidArgumentException('Invalid key length');
        if ($fixtureKey === null) {
            $fixtureKey = '';
            for ($i = 1; $i <= $k; $i++) {
                $fixtureKey .= chr(random_int(0, 0x7e + $i - 1));
            }
        }
        $output = chr((~self::rol8($k, 7)) & 255) . $fixtureKey;
        for ($i = 0; $i < $n; $i++) {
            $output .= chr(ord($plain[$i]) ^ ord($fixtureKey[$i % $k]) ^ (($n - $i) & 255));
        }
        return $output;
    }

    public static function descramble(string $encoded): string {
        $len = strlen($encoded);
        if ($len < 3 || $len > self::MAX_PLAINTEXT_BYTES + 10)
            throw new \InvalidArgumentException('Invalid encoded frame size');
        $k = self::ror8((~ord($encoded[0])) & 255, 7);
        if ($k < 2 || $k > 9 || $len <= $k)
            throw new \InvalidArgumentException('Invalid key header');
        $n = $len - $k - 1;
        $key = substr($encoded, 1, $k);
        $plain = '';
        for ($i = 0; $i < $n; $i++) {
            $plain .= chr(ord($encoded[$k + 1 + $i]) ^ ord($key[$i % $k]) ^ (($n - $i) & 255));
        }
        return $plain;
    }

    public static function wrapInnerFrame(string $inner, ?string $fixtureKey = null): string {
        $size = strlen($inner);
        if ($size < 8 || $size > self::MAX_PLAINTEXT_BYTES
            || unpack('v', substr($inner, 0, 2))[1] !== $size)
            throw new \InvalidArgumentException('Inner LE16 size mismatch');
        $enc = self::scramble($inner, $fixtureKey);
        $total = 2 + strlen($enc);
        if ($total > 65535) throw new \InvalidArgumentException('Outer frame too large');
        return pack('v', $total) . $enc;
    }

    public static function unwrapOuterFrame(string $outer): string {
        $size = strlen($outer);
        if ($size < 6 || $size > 65535
            || unpack('v', substr($outer, 0, 2))[1] !== $size)
            throw new \InvalidArgumentException('Outer LE16 size mismatch');
        $inner = self::descramble(substr($outer, 2));
        if (strlen($inner) < 2 || unpack('v', substr($inner, 0, 2))[1] !== strlen($inner))
            throw new \InvalidArgumentException('Inner LE16 size mismatch');
        return $inner;
    }
}
