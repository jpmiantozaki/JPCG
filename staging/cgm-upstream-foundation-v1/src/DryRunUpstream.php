<?php
declare(strict_types=1);
namespace CGM\Phase40CC;

require_once __DIR__ . '/EnvelopeInspector.php';

/**
 * CGM-owned local upstream foundation. All paths FAIL CLOSED. There is no
 * provider verifier, session issuer, game socket or success-packet algorithm.
 * Reuse an existing, verified SecurePacket-compatible encoder/decoder only in
 * an owned offline/local fixture; never submit a real password to this fixture.
 */
final class DryRunUpstream
{
    /**
     * @param callable(string):string $decrypt
     * @param callable(string):string $encrypt
     * @return array{decision:string,http_status:int,protected_reply:?string,metadata:array<string,int|string|bool>}
     */
    public static function inspectAndDeny(
        string $protectedRequest,
        callable $decrypt,
        callable $encrypt,
    ): array {
        $meta = EnvelopeInspector::inspectProtected($protectedRequest, $decrypt);
        $response = [
            'error' => true,
            'message' => 'CGM upstream not configured',
            'data' => '',
            // No approved response route exists. This integer is a deterministic
            // denial-envelope field, not evidence of LoginServer authorization.
            'servertype' => 0,
            'session' => '',
            'vip_expiry' => 0,
        ];
        $reply = null;
        try {
            $encoded = $encrypt(json_encode($response, JSON_THROW_ON_ERROR));
            if (is_string($encoded) && $encoded !== '') $reply = $encoded;
        } catch (\Throwable $e) {
            // Fail closed, never leak the exception or input packet.
        }
        return [
            'decision' => 'DENY_NO_AUTHORIZED_PROVIDER',
            'http_status' => $reply === null ? 503 : 200,
            'protected_reply' => $reply,
            'metadata' => $meta,
        ];
    }
}
