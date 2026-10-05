<?php
declare(strict_types=1);

// Membership state for this CGM database only. This is not game authentication.
function cgm_membership_state(int $expiry, int $now): string {
    if ($expiry <= 0) return 'INACTIVE';
    return $expiry > $now ? 'ACTIVE' : 'EXPIRED';
}

function cgm_membership_payload(int $expiry, int $now): array {
    $state = cgm_membership_state($expiry, $now);
    $active = $state === 'ACTIVE';
    $message = match ($state) {
        'ACTIVE' => 'CGM membership is active.',
        'EXPIRED' => 'CGM membership has expired.',
        default => 'No redeemed CGM membership was found for this account.',
    };
    return [
        'error' => !$active,
        'message' => $message,
        // No fabricated game session, relay data or upstream credentials.
        'data' => '',
        'servertype' => 0,
        'session' => '',
        'vip_expiry' => max(0, $expiry),
        'membership_active' => $active,
        'state' => $state,
        'scope' => 'CGM_MEMBERSHIP_ONLY',
    ];
}

function cgm_membership_error(string $message): array {
    $v = cgm_membership_payload(0, time());
    $v['message'] = $message;
    $v['state'] = 'ERROR';
    return $v;
}

function cgm_membership_lookup(PDO $pdo, string $login, int $now): array {
    // Keep identity matching identical to redeemvip.php: trimmed, case-preserved.
    $q = $pdo->prepare(
        'SELECT MAX(vip_expiry) AS vip_expiry FROM codes '
        . 'WHERE login_id = ? AND redeemed_at IS NOT NULL'
    );
    $q->execute([$login]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    return cgm_membership_payload((int)($row['vip_expiry'] ?? 0), $now);
}
