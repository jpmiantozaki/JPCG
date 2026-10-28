<?php
declare(strict_types=1);
namespace CGM\UpstreamV4;

require_once __DIR__ . '/ProviderHandoffGate.php';

use CGM\Phase40CD\AtomicOneUseAttemptStore;
use PDO;

/**
 * PostgreSQL backed one-use admission attempt ledger.
 * Stored values are keyed HMAC digests only: no email, official subject, proof,
 * credential, game packet or relay session is stored.
 * Requires the versioned SQL migration and a stable secret kept outside Git.
 * This store does not issue or validate provider/game sessions.
 */
final class PgAtomicAttemptStore implements AtomicOneUseAttemptStore
{
    public function __construct(
        private PDO $pdo,
        private string $hmacKey,
    ) {
        if (strlen($hmacKey) < 32) {
            throw new \InvalidArgumentException('An external 32+ byte stable HMAC key is required.');
        }
    }

    public function consume(string $issuer, string $subject, string $attemptId, int $validUntil): bool
    {
        if ($issuer === '' || $subject === '' ||
            preg_match('/\A[A-Za-z0-9_-]{16,64}\z/D', $attemptId) !== 1 ||
            $validUntil <= 0) {
            return false;
        }
        // Uniqueness is GLOBAL to an attemptId. A replay from a different
        // account does not create a second consumption entry.
        $digest = hash_hmac('sha256', "cgm-upstream-attempt-v1\0" . $attemptId, $this->hmacKey);
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO cgm_upstream_admission_attempts (attempt_digest, valid_until) '
                . 'SELECT :digest, :expiry '
                . 'WHERE :expiry_check > CAST(EXTRACT(EPOCH FROM clock_timestamp()) AS BIGINT) '
                . 'ON CONFLICT (attempt_digest) DO NOTHING RETURNING attempt_digest'
            );
            if ($stmt === false || !$stmt->execute([
                ':digest' => $digest,
                ':expiry' => $validUntil,
                ':expiry_check' => $validUntil,
            ])) return false;
            $received = $stmt->fetchColumn();
            return is_string($received) && hash_equals($digest, $received);
        } catch (\Throwable $e) {
            // Fail closed; never log the DB error, attempt, identity, or inputs.
            return false;
        }
    }
}
