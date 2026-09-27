<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const DB_PATH = '/var/data/jpcg.sqlite';
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
    if (!is_dir(dirname(DB_PATH))) @mkdir(dirname(DB_PATH), 0770, true);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode=WAL;');
    $pdo->exec('CREATE TABLE IF NOT EXISTS codes(
        code_hash TEXT PRIMARY KEY,
        code_hint TEXT NOT NULL,
        vip_days INTEGER NOT NULL,
        created_at INTEGER NOT NULL,
        redeemed_at INTEGER,
        login_id TEXT,
        vip_expiry INTEGER
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS sessions(
        session TEXT PRIMARY KEY,
        login_id TEXT NOT NULL,
        relay_data TEXT NOT NULL,
        stage INTEGER NOT NULL,
        vip_expiry INTEGER NOT NULL,
        session_expiry INTEGER NOT NULL,
        created_at INTEGER NOT NULL
    )');
    return $pdo;
}
function code_hash(string $code): string {
    return hash('sha256', strtoupper($code));
}
function admin_ok(): bool {
    $expected = getenv('JPCG_ADMIN_KEY') ?: '';
    $provided = $_SERVER['HTTP_X_ADMIN_KEY'] ?? value('admin_key');
    return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
}
function relay(bool $error, string $message, string $data, int $type, string $session, int $expiry): array {
    return [
        'error'=>$error, 'message'=>$message, 'data'=>$data,
        'servertype'=>$type, 'session'=>$session, 'vip_expiry'=>$expiry
    ];
}
function clean_sessions(PDO $db): void {
    $q=$db->prepare('DELETE FROM sessions WHERE session_expiry < ?');
    $q->execute([time()]);
}
