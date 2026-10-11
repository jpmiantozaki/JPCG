<?php
declare(strict_types=1);

// Add alongside existing public/_common.php and public/_cgm_membership.php.
// Read-only CGM eligibility; never issues an official game session.
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_cgm_membership.php';
require_once __DIR__ . '/_authfirst_resolve.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function authfirst_reply(string $state, int $expiry, int $code = 200): never {
    $now = time();
    $eligible = $state === 'ACTIVE' && $expiry > $now;
    if (!$eligible && $state === 'ACTIVE') $state = 'EXPIRED';
    http_response_code($code);
    echo json_encode([
        'scope' => 'CGM_MEMBERSHIP_ONLY',
        'state' => $state,
        'eligible_to_attempt' => $eligible,
        'game_authorized' => false,
        'vip_expiry' => max(0, $expiry),
        'server_time' => $now,
        'message' => match ($state) {
            'ACTIVE' => 'CGM membership active.',
            'EXPIRED' => 'CGM membership expired.',
            'INACTIVE' => 'CGM membership inactive or not found.',
            default => 'Membership lookup unavailable.'
        },
    ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    authfirst_reply('ERROR', 0, 405);
}
$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '', 2)[0]));
if ($contentType !== 'application/json') authfirst_reply('ERROR', 0, 415);
$raw = file_get_contents('php://input', false, null, 0, 1025);
if ($raw === false || strlen($raw) > 1024) authfirst_reply('ERROR', 0, 400);
try { $request = json_decode($raw, true, 4, JSON_THROW_ON_ERROR); }
catch (JsonException $e) { authfirst_reply('ERROR', 0, 400); }
if (!is_array($request) || array_keys($request) !== ['login_id'] || !is_string($request['login_id'])) {
    authfirst_reply('ERROR', 0, 400);
}
$login = $request['login_id'];
// Match the current redemption/verify logic exactly: do not lowercase.
if ($login !== trim($login) || strlen($login) < 1 || strlen($login) > 320 ||
    preg_match('/[\x00-\x1F\x7F~]/', $login)) {
    authfirst_reply('ERROR', 0, 400);
}
try {
    $pdo = db();
    $now = time();
    $result = authfirst_resolve($login, static function (string $candidate) use ($pdo, $now): array {
        return cgm_membership_lookup($pdo, $candidate, $now);
    });
    $state = (string)($result['state'] ?? 'ERROR');
    $expiry = (int)($result['vip_expiry'] ?? 0);
    if (!in_array($state, ['ACTIVE', 'EXPIRED', 'INACTIVE'], true)) {
        authfirst_reply('ERROR', 0, 503);
    }
    authfirst_reply($state, $expiry);
} catch (Throwable $e) {
    error_log('[CGM_AUTHFIRST] lookup_failure');
    authfirst_reply('ERROR', 0, 503);
}
