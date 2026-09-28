<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

function database_url_parts(string $url): array {
    $p = parse_url($url);
    if ($p === false || empty($p['host']) || empty($p['path'])) {
        throw new RuntimeException('DATABASE_URL is invalid');
    }
    return $p;
}

try {
    $login = trim((string)($_GET['login'] ?? ''));
    if ($login === '') out(400, ['ok'=>false, 'state'=>'missing_login']);

    $dbUrl = getenv('DATABASE_URL') ?: '';
    $p = database_url_parts($dbUrl);
    $host = $p['host'];
    $port = $p['port'] ?? 5432;
    $db   = ltrim($p['path'], '/');
    $user = urldecode($p['user'] ?? '');
    $pass = urldecode($p['pass'] ?? '');

    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$db};sslmode=require",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // JPCG v2 stores a SHA-256 hash of the bound login identifier.
    $hash = hash('sha256', $login);

    // Find the most recent entitlement for this account. This endpoint returns
    // entitlement state only; it never returns a code, session, or relay token.
    $stmt = $pdo->prepare(
        "SELECT vip_expiry
           FROM vip_codes
          WHERE login_id_hash = :h
            AND redeemed_at IS NOT NULL
          ORDER BY vip_expiry DESC NULLS LAST
          LIMIT 1"
    );
    $stmt->execute([':h'=>$hash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['vip_expiry'])) {
        out(200, ['ok'=>true, 'state'=>'not_activated', 'vip_expiry'=>null]);
    }

    $expiry = (int)$row['vip_expiry'];
    $now = time();
    out(200, [
        'ok'=>true,
        'state'=>($expiry > $now ? 'active' : 'expired'),
        'vip_expiry'=>$expiry
    ]);
} catch (Throwable $e) {
    error_log('[JPCG_ACCOUNT_STATUS_ERROR] '.$e->getMessage());
    out(500, ['ok'=>false, 'state'=>'server_error']);
}
