<?php
declare(strict_types=1);

// Port of SecurePacket.SecureEncryption from the unmodified base APK.
// Base IL2CPP facts used here:
// - RandomString(6) alphabet: abcdef0123456789
// - XOR key repeats across the plaintext
// - encrypted XOR bytes are lowercase hex ("x2")
// - packet separator is ':'
// - tail = 6-char key + 40-char checksum + switching digit
// - switching is Random.Next(3), i.e. 0, 1, or 2

function cgm_random_key(): string {
    $alphabet = 'abcdef0123456789';
    $out = '';
    for ($i=0; $i<6; $i++) $out .= $alphabet[random_int(0, strlen($alphabet)-1)];
    return $out;
}

function cgm_checksum(string $packetWithKey, int $switching, string $key): string {
    $packetHash = sha1($packetWithKey); // lowercase 40-char hex
    $keyHash = sha1($key);
    return match ($switching) {
        0 => substr($packetHash,0,20).substr($keyHash,20,20),
        1 => substr($packetHash,20,20).substr($keyHash,0,20),
        2 => substr($keyHash,20,20).substr($packetHash,0,20),
        default => 'Error',
    };
}

function cgm_encrypt_packet(string $raw, ?string $key=null, ?int $switching=null): string {
    $key ??= cgm_random_key();
    $switching ??= random_int(0,2);
    if (!preg_match('/^[a-f0-9]{6}$/', $key)) throw new InvalidArgumentException('Invalid key');
    if ($switching < 0 || $switching > 2) throw new InvalidArgumentException('Invalid switching');

    $hex = '';
    $klen = strlen($key);
    for ($i=0, $n=strlen($raw); $i<$n; $i++) {
        $hex .= sprintf('%02x', ord($raw[$i]) ^ ord($key[$i % $klen]));
    }
    $packetWithKey = $hex.':'.$key;
    return $packetWithKey.cgm_checksum($packetWithKey,$switching,$key).(string)$switching;
}

function cgm_decrypt_packet(string $encrypted): string {
    $pos = strpos($encrypted, ':');
    if ($pos === false) throw new RuntimeException('Malformed protected packet');
    $packet = substr($encrypted,0,$pos);
    $tail = substr($encrypted,$pos+1);
    if ($packet === '' || (strlen($packet)%2)!==0 || !ctype_xdigit($packet)) throw new RuntimeException('Malformed packet hex');
    if (strlen($tail) !== 47) throw new RuntimeException('Malformed packet tail');

    $key = substr($tail,0,6);
    $checksum = substr($tail,6,40);
    $switchChar = substr($tail,46,1);
    if (!preg_match('/^[a-f0-9]{6}$/',$key) || !preg_match('/^[a-f0-9]{40}$/',$checksum) || !preg_match('/^[0-2]$/',$switchChar)) {
        throw new RuntimeException('Malformed packet fields');
    }
    $switching=(int)$switchChar;
    $packetWithKey=$packet.':'.$key;
    $expected=cgm_checksum($packetWithKey,$switching,$key);
    if (!hash_equals($expected,$checksum)) throw new RuntimeException('Protected packet checksum mismatch');

    $raw='';
    $klen=strlen($key);
    $j=0;
    for ($i=0,$n=strlen($packet); $i<$n; $i+=2,$j++) {
        $raw .= chr(hexdec(substr($packet,$i,2)) ^ ord($key[$j % $klen]));
    }
    return $raw;
}

function cgm_secure_respond(array $payload, int $status=200): never {
    $json=json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($json===false) throw new RuntimeException('JSON encoding failed');
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo cgm_encrypt_packet($json);
    exit;
}
