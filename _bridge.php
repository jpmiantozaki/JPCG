<?php
declare(strict_types=1);

// CGM Translation v1 control bridge.
// The APK-facing hostname changes, but the five working modified-APK
// endpoint contracts remain untouched by forwarding each request to the
// same path on m.cabahug.xyz.

function cgm_bridge(string $path): never {
    $allowed = ['analytics.php','authenticate.php','redeemvip.php','session.php','verify.php'];
    if (!in_array($path, $allowed, true)) {
        http_response_code(404);
        exit;
    }

    $query = (string)($_SERVER['QUERY_STRING'] ?? '');
    $url = 'https://m.cabahug.xyz/' . $path . ($query !== '' ? '?' . $query : '');
    $body = false;
    $status = 502;
    $contentType = 'text/plain; charset=utf-8';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => (string)($_SERVER['HTTP_USER_AGENT'] ?? 'CGM-Translation-v1'),
        ]);
        $body = curl_exec($ch);
        if ($body !== false) {
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            if (is_string($ct) && $ct !== '') $contentType = $ct;
        } else {
            error_log('[CGM_BRIDGE_ERROR] path='.$path.' curl='.curl_error($ch));
        }
        curl_close($ch);
    } elseif ((bool)ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http'=>[
            'method'=>'GET',
            'timeout'=>20,
            'ignore_errors'=>true,
            'header'=>'User-Agent: '.((string)($_SERVER['HTTP_USER_AGENT'] ?? 'CGM-Translation-v1'))."\r\n",
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#i', $h, $m)) $status=(int)$m[1];
                if (stripos($h, 'Content-Type:')===0) $contentType=trim(substr($h,13));
            }
        }
    }

    if ($body === false) {
        http_response_code(502);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'CGM bridge upstream unavailable';
        exit;
    }

    error_log('[CGM_BRIDGE] path='.$path.' query_len='.strlen($query).' status='.$status.' body_len='.strlen((string)$body));
    http_response_code($status > 0 ? $status : 200);
    header('Content-Type: '.$contentType);
    header('Cache-Control: no-store');
    echo $body;
    exit;
}
