<?php
declare(strict_types=1);

/**
 * STAGING ONLY: encode one client-compatible, NEGATIVE CGM membership result.
 *
 * The original Unity RelayServerData callback has an error=true branch which
 * recognizes message === 'VIP' and opens the existing VIP/redemption UI.
 * This is CGM's own denial; it MUST NOT be described as upstream-originated.
 * No success, relay session, login-server packet or game authorization exists.
 *
 * Usage: pass the real CGM backend cgm_encrypt_packet (public/_secure_packet.php).
 */
final class CgmNativeVipDenialReply
{
    private const PERMITTED_DENIAL = 'CGM_MEMBERSHIP_NOT_ELIGIBLE';

    /**
     * @param array<string,mixed> $decisionMetadata Metadata from CgmStage0ProviderGate.
     * @param callable(string):string $encrypt The original-compatible secure packet encoder.
     * @return ?string Protected negative response, or null if this denial doesn't apply.
     */
    public static function encode(array $decisionMetadata, callable $encrypt): ?string
    {
        if (($decisionMetadata['status'] ?? null) !== self::PERMITTED_DENIAL
            || ($decisionMetadata['may_forward'] ?? null) !== false
            || ($decisionMetadata['claimed_login_eligible'] ?? null) !== false
            || ($decisionMetadata['is_game_authenticated'] ?? null) !== false
            || ($decisionMetadata['is_official_identity_bound'] ?? null) !== false) {
            return null;
        }

        $body = json_encode([
            'error' => true,
            'message' => 'VIP',
            'data' => '',
            'servertype' => 0,
            'session' => '',
            'vip_expiry' => 0,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $protected = $encrypt($body);
        if (!is_string($protected) || $protected === '') {
            throw new RuntimeException('Client denial encoder unavailable');
        }
        return $protected;
    }
}
