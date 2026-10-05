<?php
declare(strict_types=1);

// Routing test only. Preserve the original server's protocol and decisions.
// Does not grant CGM membership, invent sessions, or modify game entitlement.
function cgm_session_bridge(string $endpoint): never
{
    $allowed = ['authenticate.php', 'session.php', 'analytics.php'];
    if (!in_array($endpoint, $allowed, true)) {
        http_response_code(404);
        exit;
    }
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'HEAD', 'POST'], true)) {
        http_response_code(405);
        header('Allow: GET, HEAD, POST');
        exit;
    }
    $origin = getenv('CGM_ORIGINAL_ORIGIN') ?: 'https://anlgarden.com';
    $parts = parse_url($origin);
    if ($parts === false || ($parts['scheme'] ?? '') !== 'https'
        || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
        || isset($parts['query']) || isset($parts['fragment'])
        || !in_array($parts['path'] ?? '', ['', '/'], true)
        || in_array(strtolower($parts['host']), ['jpcg.onrender.com', 'wtn.pages.dev'], true)) {
        cgm_bridge_failure($endpoint, 'configuration');
    }
    $query = (string)($_SERVER['QUERY_STRING'] ?? '');
    $target = rtrim($origin, '/') . '/' . $endpoint . ($query === '' ? '' : '?' . $query);
    $headers = ['Accept-Encoding: identity', 'Connection: close'];
    foreach (['HTTP_USER_AGENT' => 'User-Agent', 'HTTP_ACCEPT' => 'Accept',
        'CONTENT_TYPE' => 'Content-Type', 'HTTP_COOKIE' => 'Cookie',
        'HTTP_AUTHORIZATION' => 'Authorization'] as $key => $name) {
        $value = (string)($_SERVER[$key] ?? '');
        if ($value !== '' && !str_contains($value, "\r") && !str_contains($value, "\n")) {
            $headers[] = $name . ': ' . $value;
        }
    }
    $raw = $method === 'POST' ? file_get_contents('php://input') : '';
    if ($raw === false) cgm_bridge_failure($endpoint, 'request_body');
    $options = ['method' => $method, 'header' => implode("\r\n", $headers),
        'content' => $raw, 'timeout' => 25, 'ignore_errors' => true,
        'follow_location' => 0, 'max_redirects' => 0, 'protocol_version' => 1.1];
    $context = stream_context_create(['http' => $options,
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $stream = @fopen($target, 'rb', false, $context);
    if ($stream === false) cgm_bridge_failure($endpoint, 'connection');
    $metadata = stream_get_meta_data($stream);
    $upstreamHeaders = $metadata['wrapper_data'] ?? [];
    $status = 0;
    $responseHeaders = [];
    foreach ($upstreamHeaders as $line) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/i', $line, $match)) {
            $status = (int)$match[1];
            $responseHeaders = [];
        } elseif (str_contains($line, ':')) {
            [$name, $value] = explode(':', $line, 2);
            if (in_array(strtolower($name), ['content-type', 'content-encoding',
                'location', 'set-cookie', 'www-authenticate', 'retry-after'], true)) {
                $responseHeaders[] = $name . ':' . $value;
            }
        }
    }
    $body = stream_get_contents($stream, 8 * 1024 * 1024 + 1);
    $metadata = stream_get_meta_data($stream);
    fclose($stream);
    if ($body === false || ($metadata['timed_out'] ?? false)
        || strlen($body) > 8 * 1024 * 1024 || $status < 200 || $status > 599) {
        cgm_bridge_failure($endpoint, 'response');
    }
    http_response_code($status);
    foreach ($responseHeaders as $line) header($line, false);
    header('Cache-Control: no-store');
    header('X-CGM-Backend: cgm-render-session-relay-v1');
    // Log metadata only: never query strings, credentials, bodies or cookies.
    error_log('[CGM_SESSION_BRIDGE] stage=cgm-render-session-relay-v1 path=/'
        . $endpoint . ' method=' . $method . ' upstream_host=' . $parts['host']
        . ' status=' . $status . ' body_bytes=' . strlen($body));
    if ($method !== 'HEAD') echo $body;
    exit;
}

function cgm_bridge_failure(string $endpoint, string $reason): never
{
    error_log('[CGM_SESSION_BRIDGE_ERROR] path=/' . $endpoint . ' reason=' . $reason);
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-CGM-Backend: cgm-render-session-relay-v1');
    echo 'CGM session upstream unavailable';
    exit;
}
