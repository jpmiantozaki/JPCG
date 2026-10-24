<?php
declare(strict_types=1);

/**
 * CGM STAGING ONLY: preflight the ORIGINAL stage-0 ANLGarden login-relay request.
 * This module does not authenticate game accounts or create relay/game sessions.
 * Never log the request, its decrypted bytes, account identifiers, or passwords.
 */

/** @return array{kind:string,login_id?:string} */
function cgm_stage0_extract(string $protected, callable $decrypt): array
{
    if ($protected === '' || strlen($protected) > 65536) {
        return ['kind' => 'INVALID'];
    }
    try {
        $raw = $decrypt($protected);
        if (!is_string($raw) || strlen($raw) > 32768) return ['kind' => 'INVALID'];
        // The original RelayWebManager envelope is packetHex~stage~session.
        $parts = explode('~', $raw);
        if (count($parts) !== 3) return ['kind' => 'INVALID'];
        if ($parts[1] !== '0') return ['kind' => 'OTHER_STAGE'];
        // Stage-0 login must not supply an existing relay session.
        if ($parts[2] !== '') return ['kind' => 'INVALID'];
        $hex = $parts[0];
        if (strlen($hex) < 20 || strlen($hex) > 16384 || strlen($hex) % 2 !== 0 || !ctype_xdigit($hex)) {
            return ['kind' => 'INVALID'];
        }
        $packet = hex2bin($hex);
        if ($packet === false || strlen($packet) < 11) return ['kind' => 'INVALID'];
        $packetSize = unpack('vlength', substr($packet, 0, 2));
        if (!is_array($packetSize) || $packetSize['length'] !== strlen($packet)) return ['kind' => 'INVALID'];
        // 2-byte length, 4-byte original sequence counter, then two command bytes 1,1.
        if (ord($packet[6]) !== 1 || ord($packet[7]) !== 1) return ['kind' => 'INVALID'];
        $cursor = 8;
        $size = strlen($packet);
        $strings = [];
        for ($i = 0; $i < 3; ++$i) {
            if ($cursor >= $size) return ['kind' => 'INVALID'];
            $n = ord($packet[$cursor++]);
            if ($n < 1 || $cursor + $n > $size) return ['kind' => 'INVALID'];
            if ($i === 0) $strings[] = substr($packet, $cursor, $n);
            // The other two fields are password and client version. Skip, never decode/log.
            $cursor += $n;
        }
        if ($cursor !== $size) return ['kind' => 'INVALID'];
        $id = $strings[0];
        // The observed standard (non-Facebook) client prefixes its login ID with INPP.
        // Fail closed on unverified alternate prefixes or unsafe/non-ASCII identifiers.
        if (!str_starts_with($id, 'INPP.') || strlen($id) > 255 ||
            preg_match('/\AINPP\.[A-Za-z0-9._@+\-]{1,200}\z/D', $id) !== 1) {
            return ['kind' => 'INVALID'];
        }
        return ['kind' => 'STAGE0', 'login_id' => $id];
    } catch (Throwable $e) {
        // Includes an invalid SecurePacket checksum; never reveal its contents.
        return ['kind' => 'INVALID'];
    }
}

/**
 * Callable $lookup receives exact prefixed login_id and must return existing
 * cgm_membership_lookup payload: state and vip_expiry. Neither field is a
 * provider-issued login result.
 *
 * @return array{decision:string, reason:string, expiry:int}
 */
function cgm_stage0_vip_decision(string $protected, callable $decrypt, callable $lookup, int $now): array
{
    $frame = cgm_stage0_extract($protected, $decrypt);
    if ($frame['kind'] !== 'STAGE0') {
        return ['decision' => 'DENY', 'reason' => $frame['kind'] === 'OTHER_STAGE'
            ? 'UNSUPPORTED_STAGE' : 'INVALID_REQUEST', 'expiry' => 0];
    }
    try {
        $record = $lookup($frame['login_id']);
        if (!is_array($record)) throw new RuntimeException('Bad lookup');
        $state = $record['state'] ?? null;
        $expiry = $record['vip_expiry'] ?? null;
        if (!is_string($state) || !is_int($expiry)) throw new RuntimeException('Bad response');
        if ($state === 'ACTIVE' && $expiry > $now) {
            return ['decision' => 'FORWARD_UNCHANGED', 'reason' => 'CGM_ACTIVE', 'expiry' => $expiry];
        }
        if ($state === 'INACTIVE' || $state === 'EXPIRED' || $state === 'ACTIVE') {
            return ['decision' => 'DENY', 'reason' => 'CGM_NOT_ACTIVE', 'expiry' => max(0, $expiry)];
        }
        throw new RuntimeException('Unexpected state');
    } catch (Throwable $e) {
        return ['decision' => 'DENY', 'reason' => 'LOOKUP_UNAVAILABLE', 'expiry' => 0];
    }
}

/**
 * Map admission into the original encrypted MGRelayResponse *failure* schema.
 * On ACTIVE no session reply is generated: caller must forward the ORIGINAL
 * unchanged request to its genuine, permitted session processor or fail closed.
 * This helper never returns error=false and never forges session/data tokens.
 *
 * @return array{error:bool,message:string,data:string,session:string,servertype:int,vip_expiry:int}
 */
function cgm_stage0_rejection(array $decision): array
{
    $notActive = $decision['reason'] === 'CGM_NOT_ACTIVE';
    return ['error' => true, 'message' => $notActive ? 'VIP' : 'CGM service unavailable',
        'data' => '', 'session' => '', 'servertype' => 0, 'vip_expiry' => 0];
}
