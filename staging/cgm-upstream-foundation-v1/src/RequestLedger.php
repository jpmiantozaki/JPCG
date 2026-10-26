<?php
declare(strict_types=1);
namespace CGM\Phase40CC;

/**
 * Host-only demonstration of per-attempt/per-request lifecycle correlation.
 * IDs are local diagnostic labels, NOT relay tokens, provider IDs or credentials.
 * This in-memory ledger is NOT transactionally durable across PHP workers.
 */
final class RequestLedger
{
    private array $attempts = [];
    private array $requests = [];

    public function begin(int $now, int $ttlSeconds = 30): ?string
    {
        if ($now <= 0 || $ttlSeconds < 1 || $ttlSeconds > 120) return null;
        $id = bin2hex(random_bytes(16));
        $this->attempts[$id] = ['expires' => $now + $ttlSeconds, 'active' => true];
        return $id;
    }

    public function enqueue(string $attemptId, int $stage, int $now): ?string
    {
        if (!in_array($stage, [0,1], true) || !$this->active($attemptId, $now)) return null;
        $id = bin2hex(random_bytes(16));
        $this->requests[$id] = ['attempt' => $attemptId, 'stage' => $stage, 'pending' => true];
        return $id;
    }

    public function deliver(string $requestId, string $attemptId, int $stage, int $now): string
    {
        $r = $this->requests[$requestId] ?? null;
        if (!is_array($r) || !$r['pending']) return 'NOT_PENDING';
        if ($r['attempt'] !== $attemptId) return 'ATTEMPT_MISMATCH';
        if ($r['stage'] !== $stage) return 'STAGE_MISMATCH';
        if (!$this->active($attemptId, $now)) return 'ATTEMPT_INACTIVE_OR_EXPIRED';
        unset($this->requests[$requestId]);
        // This only establishes local event correlation, NEVER game acceptance.
        return 'MATCHED_LOCAL_DELIVERY_ONLY';
    }

    public function cancel(string $attemptId): void
    {
        if (isset($this->attempts[$attemptId])) $this->attempts[$attemptId]['active'] = false;
        foreach ($this->requests as $key => $v) {
            if ($v['attempt'] === $attemptId) unset($this->requests[$key]);
        }
    }

    /** Purge expired local attempts and pending callbacks; never a provider logout. */
    public function purgeExpired(int $now): void
    {
        foreach ($this->attempts as $id => $entry) {
            if ($entry['expires'] <= $now || $entry['active'] !== true) {
                $this->cancel($id);
                unset($this->attempts[$id]);
            }
        }
    }

    public function pendingCount(string $attemptId): int
    {
        $n = 0;
        foreach ($this->requests as $v) if ($v['attempt'] === $attemptId) $n++;
        return $n;
    }

    private function active(string $attemptId, int $now): bool
    {
        $a = $this->attempts[$attemptId] ?? null;
        return is_array($a) && $a['active'] === true && $a['expires'] > $now;
    }
}
