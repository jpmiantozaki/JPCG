<?php
declare(strict_types=1);
require_once __DIR__ . '/ClientPacketInspector.php';

/**
 * OFFLINE RESEARCH ONLY. Never mount this file as public/session.php.
 * Explores whether relaying the original client packet unchanged is sufficient
 * for the mobile HTTP envelope. Does NOT prove official game acceptance.
 * No socket/HTTP calls are made; web execution is explicitly refused.
 */
final class CgmZeroTransformCandidate
{
    private const MAX_REQUEST = 65536;
    private const TOKEN_TTL = 120;

    /**
     * @param array<string,array{expires:int,id:string,verified:bool}> $state
     * @param callable(string):string $decrypt existing SecurePacket decoder
     * @param callable(string):string $encrypt existing SecurePacket encoder
     * @param callable(string):array $membershipLookup exact CGM ID lookup
     * @return array{decision:string,protected_reply:?string,metadata:array<string,bool|string|int>}
     */
    public static function process(
        string $protected,
        callable $decrypt,
        callable $encrypt,
        callable $membershipLookup,
        array &$state,
        int $now
    ): array {
        $base = ['officially_authenticated'=>false, 'relay_packet_unmodified'=>false,
            'candidate_only'=>true, 'live_server_contacted'=>false];
        $blocked = static fn(string $why): array => [
            'decision'=>'BLOCK', 'protected_reply'=>null,
            'metadata'=>['reason'=>$why]+$base,
        ];
        if (PHP_SAPI !== 'cli') return $blocked('OFFLINE_CLI_ONLY');
        if ($now <= 0 || strlen($protected) < 1 || strlen($protected) > self::MAX_REQUEST) {
            return $blocked('INVALID_INPUT');
        }
        try {
            $raw = $decrypt($protected);
            if (!is_string($raw) || strlen($raw) > 32768) return $blocked('INVALID_INPUT');
            $kind = CgmClientPacketInspector::inspectEnvelope($raw);
            if (($kind['kind'] ?? null) === 'INVALID') return $blocked('INVALID_FRAME');
            $parts = explode('~', $raw);
            if (count($parts) !== 3) return $blocked('INVALID_ENVELOPE');
            [$hex, $stageText, $relayToken] = $parts;
            $stage = (int)$stageText;
            $wirePacket = hex2bin($hex);
            if ($wirePacket === false) return $blocked('INVALID_PACKET');

            if ($stage === 0) {
                if (($kind['kind'] ?? null) !== 'LOGIN_SUBMIT') return $blocked('UNMAPPED_STAGE0');
                $id = self::exactLogin($wirePacket);
                if ($id === null) return $blocked('INVALID_LOGIN_CLAIM');
                $member = $membershipLookup($id);
                $active = is_array($member)
                    && ($member['scope'] ?? null) === 'CGM_MEMBERSHIP_ONLY'
                    && ($member['state'] ?? null) === 'ACTIVE'
                    && is_int($member['vip_expiry'] ?? null)
                    && $member['vip_expiry'] > $now
                    && $member['vip_expiry'] <= 4294967295;
                if (!$active) {
                    return self::reply('CGM_VIP_REJECTION', $encrypt, [
                        'error'=>true,'message'=>'VIP','data'=>'','servertype'=>0,
                        'session'=>'','vip_expiry'=>0,
                    ], false);
                }
                $token = bin2hex(random_bytes(20));
                $state[$token] = ['expires'=>min($now+self::TOKEN_TTL, $member['vip_expiry']),
                    'id'=>$id, 'verified'=>false];
                // UNPROVEN: the original upstream may transform bytes before
                // forwarding them. This is a test candidate, not game success.
                try {
                    return self::reply('UNTRANSFORMED_RELAY_CANDIDATE', $encrypt, [
                        'error'=>false,'message'=>'','data'=>$hex,'servertype'=>0,
                        'session'=>$token,'vip_expiry'=>$member['vip_expiry'],
                    ], true);
                } catch (Throwable $e) {
                    unset($state[$token]);
                    throw $e;
                }
            }
            if ($stage !== 1 || !in_array($kind['kind'] ?? null, ['VERIFY_CCU','GAME_LOGON'], true)) {
                return $blocked('UNMAPPED_STAGE1');
            }
            $session = $state[$relayToken] ?? null;
            if (!is_array($session) || $session['expires'] <= $now) return $blocked('UNKNOWN_OR_EXPIRED_RELAY');
            // This is mobile relay continuity only: NEVER official auth.
            if ($kind['kind'] === 'GAME_LOGON' && $session['verified'] !== true) {
                return $blocked('VERIFY_CCU_FIRST_REQUIRED_FOR_SIMULATION');
            }
            $reply = self::reply('UNTRANSFORMED_RELAY_CANDIDATE', $encrypt, [
                'error'=>false,'message'=>'','data'=>$hex,'servertype'=>1,
                'session'=>$relayToken,'vip_expiry'=>0,
            ], true);
            if ($kind['kind'] === 'VERIFY_CCU') $state[$relayToken]['verified'] = true;
            return $reply;
        } catch (Throwable $exception) {
            // Never reveal exception messages, credentials or relay tokens.
            return $blocked('DEPENDENCY_FAILURE');
        }
    }

    /** @param callable(string):string $encrypt @param array<string,mixed> $payload */
    private static function reply(string $decision, callable $encrypt, array $payload, bool $forward): array
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $protected = $encrypt($json);
        if (!is_string($protected) || $protected === '') throw new RuntimeException('Encoder failed');
        return [
            'decision'=>$decision,
            'protected_reply'=>$protected,
            'metadata'=>[
                'candidate_only'=>true,'officially_authenticated'=>false,
                'relay_packet_unmodified'=>$forward,'live_server_contacted'=>false,
                'response_type'=>$payload['servertype'],
                'mobile_relay_token_present'=>($payload['session'] ?? '') !== '',
            ],
        ];
    }

    private static function exactLogin(string $packet): ?string
    {
        if (strlen($packet) < 10) return null;
        $length=ord($packet[8]);
        if ($length <= 5 || 9+$length > strlen($packet)) return null;
        $id=substr($packet,9,$length);
        if (!(str_starts_with($id,'INPP.') || str_starts_with($id,'INFB.'))
            || preg_match('/[\x00-\x1f\x7f~]/',$id)) return null;
        return $id;
    }
}
