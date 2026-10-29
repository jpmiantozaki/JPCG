<?php
declare(strict_types=1);
namespace CGM\Phase34D;

/**
 * LOCAL-ONLY lifecycle correlation for original mobile relay callbacks.
 * It neither receives game credentials nor issues official provider sessions.
 *
 * Why distinct from Phase40CC RequestLedger:
 * - A callback is bound to the submit-time *socket generation*, not the
 *   mutable current manager/session at delivery time.
 * - A reconnect or close invalidates outstanding requests on that service.
 * - Stage 0 requires LoginServer, Stage 1 requires GameServer.
 * - Neither positive nor negative stale replies can be delivered as current.
 * - Multiple concurrent Stage1 requests may complete out of order.
 *
 * This state is intentionally in memory and is NOT durable across workers.
 * A production integration would need a supported original-client extension
 * and independent authorized provider verification. NEVER use this to turn
 * an upstream VIP denial into a positive session response.
 */
final class GenerationBoundRelayLedger
{
    private array $attempts = [];
    private array $requests = [];

    private const SERVICES = ['LOGIN', 'GAME'];
    private const MAX_TTL = 120;

    public function begin(int $now, int $ttlSeconds = 60): ?string
    {
        if ($now <= 0 || $ttlSeconds < 1 || $ttlSeconds > self::MAX_TTL) return null;
        $id = bin2hex(random_bytes(16));
        $this->attempts[$id] = [
            'expires' => $now + $ttlSeconds,
            'active' => true,
            'sockets' => [
                'LOGIN' => ['generation' => 0, 'open' => false],
                'GAME' => ['generation' => 0, 'open' => false],
            ],
        ];
        return $id;
    }

    /** Returns a local generation number, NOT a game session identity. */
    public function openSocket(string $attemptId, string $service, int $now): ?int
    {
        if (!in_array($service, self::SERVICES, true) || !$this->active($attemptId, $now)) {
            return null;
        }
        $current = $this->attempts[$attemptId]['sockets'][$service]['generation'];
        if ($current >= PHP_INT_MAX) {
            $this->cancel($attemptId);
            return null;
        }
        $next = $current + 1;
        $this->attempts[$attemptId]['sockets'][$service] = [
            'generation' => $next, 'open' => true,
        ];
        $this->invalidateServiceRequests($attemptId, $service);
        return $next;
    }

    /** Every current pending callback on the closed service is retired. */
    public function closeSocket(string $attemptId, string $service): bool
    {
        if (!in_array($service, self::SERVICES, true) || !isset($this->attempts[$attemptId])) {
            return false;
        }
        // Keep the last generation so a reopen cannot reuse an old epoch.
        $this->attempts[$attemptId]['sockets'][$service]['open'] = false;
        $this->invalidateServiceRequests($attemptId, $service);
        return true;
    }

    /** @return array{request_id:string,socket_generation:int}|null */
    public function enqueue(
        string $attemptId,
        int $stage,
        string $service,
        int $now,
        int $ttlSeconds = 20
    ): ?array {
        if (!$this->stageMatchesService($stage, $service)
            || !$this->active($attemptId, $now)
            || $ttlSeconds < 1 || $ttlSeconds > 60) {
            return null;
        }
        $socket = $this->attempts[$attemptId]['sockets'][$service];
        if (!$socket['open'] || $socket['generation'] < 1) return null;
        $socketGeneration = $socket['generation'];
        $expires = min($this->attempts[$attemptId]['expires'], $now + $ttlSeconds);
        if ($expires <= $now) return null;
        $id = bin2hex(random_bytes(16));
        $this->requests[$id] = [
            'attempt' => $attemptId, 'stage' => $stage,
            'service' => $service, 'generation' => $socketGeneration,
            'expires' => $expires,
        ];
        return ['request_id' => $id, 'socket_generation' => $socketGeneration];
    }

    /**
     * This verdict ONLY permits correlation with a LOCAL pending callback.
     * Any upstream rejection remains a rejection; no byte/packet interpretation.
     *
     * @return array{classification:string,local_callback_correlated:bool,
     *     official_game_authenticated:bool,authorized_garden_session:bool}
     */
    public function deliver(
        string $requestId,
        string $attemptId,
        int $stage,
        string $service,
        int $socketGeneration,
        int $now
    ): array {
        $request = $this->requests[$requestId] ?? null;
        if ($request === null) return self::verdict('NOT_PENDING');
        if ($request['attempt'] !== $attemptId) return self::verdict('ATTEMPT_MISMATCH');
        if ($request['stage'] !== $stage) return self::verdict('STAGE_MISMATCH');
        if ($request['service'] !== $service || !$this->stageMatchesService($stage, $service)) {
            return self::verdict('SERVICE_MISMATCH');
        }
        if ($request['generation'] !== $socketGeneration || $socketGeneration <= 0) {
            return self::verdict('SOCKET_GENERATION_MISMATCH');
        }
        if (!$this->active($attemptId, $now)) {
            unset($this->requests[$requestId]);
            return self::verdict('ATTEMPT_EXPIRED_OR_CANCELLED');
        }
        if ($request['expires'] <= $now) {
            unset($this->requests[$requestId]);
            return self::verdict('REQUEST_EXPIRED');
        }
        if (!$this->attempts[$attemptId]['sockets'][$service]['open']
            || $this->attempts[$attemptId]['sockets'][$service]['generation'] !== $socketGeneration) {
            unset($this->requests[$requestId]);
            return self::verdict('SOCKET_NO_LONGER_CURRENT');
        }
        unset($this->requests[$requestId]);
        return self::verdict('MATCHED_LOCAL_CALLBACK_ONLY', true);
    }

    public function cancel(string $attemptId): void
    {
        if (isset($this->attempts[$attemptId])) $this->attempts[$attemptId]['active'] = false;
        foreach ($this->requests as $id => $entry) {
            if ($entry['attempt'] === $attemptId) unset($this->requests[$id]);
        }
    }

    /** Return counts only, never IDs, credentials, payloads or socket details. */
    public function pendingCount(string $attemptId): int
    {
        $count = 0;
        foreach ($this->requests as $entry) {
            if ($entry['attempt'] === $attemptId) $count++;
        }
        return $count;
    }

    /** Cleanup expired state, including outstanding entries. */
    public function purge(int $now): void
    {
        foreach ($this->attempts as $id => $entry) {
            if ($entry['expires'] <= $now || $entry['active'] !== true) {
                $this->cancel($id);
                unset($this->attempts[$id]);
            }
        }
        foreach ($this->requests as $id => $entry) {
            if ($entry['expires'] <= $now) unset($this->requests[$id]);
        }
    }

    private function invalidateServiceRequests(string $attemptId, string $service): void
    {
        foreach ($this->requests as $id => $entry) {
            if ($entry['attempt'] === $attemptId && $entry['service'] === $service) {
                unset($this->requests[$id]);
            }
        }
    }

    private function active(string $attemptId, int $now): bool
    {
        $entry = $this->attempts[$attemptId] ?? null;
        return is_array($entry) && $entry['active'] === true && $now > 0 && $entry['expires'] > $now;
    }

    private function stageMatchesService(int $stage, string $service): bool
    {
        return ($stage === 0 && $service === 'LOGIN') || ($stage === 1 && $service === 'GAME');
    }

    private static function verdict(string $classification, bool $matched = false): array
    {
        return [
            'classification' => $classification,
            'local_callback_correlated' => $matched,
            'official_game_authenticated' => false,
            'authorized_garden_session' => false,
        ];
    }
}
