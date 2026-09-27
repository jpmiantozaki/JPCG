<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const SESSION_TTL = 900;       // 15 minutes
const VIP_DAYS = 30;
const STORE = __DIR__ . '/../data/sessions.json';

function respond(array $v, int $status = 200): never {
    http_response_code($status);
    echo json_encode($v, JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $v = json_decode($raw, true);
    return is_array($v) ? $v : [];
}

function request_value(string $key, string $default = ''): string {
    $body = read_json_body();
    if (isset($body[$key])) return (string)$body[$key];
    if (isset($_POST[$key])) return (string)$_POST[$key];
    if (isset($_GET[$key])) return (string)$_GET[$key];
    return $default;
}

function load_store(): array {
    if (!is_file(STORE)) return [];
    $v = json_decode((string)file_get_contents(STORE), true);
    return is_array($v) ? $v : [];
}

function save_store(array $v): void {
    $tmp = STORE . '.tmp';
    file_put_contents($tmp, json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    rename($tmp, STORE);
}

function token(int $bytes = 24): string {
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function cleanup(array $store): array {
    $now = time();
    foreach ($store as $k => $s) {
        if (($s['session_expires'] ?? 0) < $now) unset($store[$k]);
    }
    return $store;
}

function relay_response(
    bool $error,
    string $message,
    string $data,
    int $serverType,
    string $session,
    int $vipExpiry
): array {
    return [
        'error' => $error,
        'message' => $message,
        'data' => $data,
        'servertype' => $serverType,
        'session' => $session,
        'vip_expiry' => $vipExpiry
    ];
}
