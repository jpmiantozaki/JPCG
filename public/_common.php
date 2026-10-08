<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const SESSION_TTL = 900;
const DEFAULT_VIP_DAYS = 30;

function respond(array $v, int $status=200): never {
    http_response_code($status);
    echo json_encode($v, JSON_UNESCAPED_SLASHES);
    exit;
}
function body(): array {
    static $b = null;
    if ($b !== null) return $b;
    $raw = file_get_contents('php://input');
    $v = $raw ? json_decode($raw, true) : [];
    $b = is_array($v) ? $v : [];
    return $b;
}
function value(string $k, string $default=''): string {
    $b = body();
    if (isset($b[$k])) return trim((string)$b[$k]);
    if (isset($_POST[$k])) return trim((string)$_POST[$k]);
    if (isset($_GET[$k])) return trim((string)$_GET[$k]);
    return $default;
}
function token(int $bytes=24): string {
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $url = getenv('DATABASE_URL') ?: '';
    if ($url === '') throw new RuntimeException('DATABASE_URL is not configured');
    $p = parse_url($url);
    if ($p === false || empty($p['host']) || empty($p['path']))
        throw new RuntimeException('DATABASE_URL is invalid');

    $host = $p['host'];
    $port = (int)($p['port'] ?? 5432);
    $name = ltrim($p['path'], '/');
    $user = isset($p['user']) ? rawurldecode($p['user']) : '';
    $pass = isset($p['pass']) ? rawurldecode($p['pass']) : '';
    parse_str($p['query'] ?? '', $opts);
    $sslmode = preg_replace('/[^a-z-]/i', '', (string)($opts['sslmode'] ?? 'prefer')) ?: 'prefer';

    $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode={$sslmode}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('CREATE TABLE IF NOT EXISTS codes(
        code_hash TEXT PRIMARY KEY,
        code_hint TEXT NOT NULL,
        code_full TEXT NULL,
        vip_days INTEGER NOT NULL,
        created_at BIGINT NOT NULL,
        redeemed_at BIGINT NULL,
        login_id TEXT NULL,
        vip_expiry BIGINT NULL
    )');
    // Additive migration: existing codes and memberships remain intact.
    $pdo->exec('ALTER TABLE codes ADD COLUMN IF NOT EXISTS code_full TEXT NULL');
    $pdo->exec('CREATE TABLE IF NOT EXISTS sessions(
        session TEXT PRIMARY KEY,
        login_id TEXT NOT NULL,
        relay_data TEXT NOT NULL,
        stage INTEGER NOT NULL,
        vip_expiry BIGINT NOT NULL,
        session_expiry BIGINT NOT NULL,
        created_at BIGINT NOT NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_sessions_expiry ON sessions(session_expiry)');
    return $pdo;
}
function code_hash(string $code): string { return hash('sha256', strtoupper($code)); }
function admin_ok(): bool {
    $expected = getenv('JPCG_ADMIN_KEY') ?: '';
    $provided = $_SERVER['HTTP_X_ADMIN_KEY'] ?? value('admin_key');
    return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
}
function relay(bool $error, string $message, string $data, int $type, string $session, int $expiry): array {
    return ['error'=>$error,'message'=>$message,'data'=>$data,'servertype'=>$type,'session'=>$session,'vip_expiry'=>$expiry];
}
function clean_sessions(PDO $db): void {
    $q=$db->prepare('DELETE FROM sessions WHERE session_expiry < ?');
    $q->execute([time()]);
}
