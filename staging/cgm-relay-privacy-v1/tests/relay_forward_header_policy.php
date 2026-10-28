<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/RelayForwardHeaderPolicy.php';
$n = 0;
function check(bool $pass, string $label): void {
    global $n;
    if (!$pass) { fwrite(STDERR, "FAIL {$label}\n"); exit(1); }
    $n++;
}
$server = [
    'HTTP_USER_AGENT' => 'UnityPlayer/2021.3.45f1',
    'HTTP_ACCEPT' => '*/*',
    'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
    'HTTP_COOKIE' => 'SESSION=fictional-secret',
    'HTTP_AUTHORIZATION' => 'Bearer fictional-secret',
    'HTTP_REFERER' => 'https://fake.example.invalid/login?password=fictional-secret',
    'HTTP_X_FORWARDED_FOR' => '10.10.10.10',
    'HTTP_HOST' => 'test.example.invalid',
];
$get = CgmRelayForwardHeaderPolicy::select($server, 'GET');
check(count($get) === 4, 'GET uses fixed plus two allowlisted headers');
check(in_array('User-Agent: UnityPlayer/2021.3.45f1', $get, true), 'preserves Unity UA');
check(in_array('Accept: */*', $get, true), 'preserves Accept');
check(!in_array('Content-Type: application/x-www-form-urlencoded', $get, true), 'GET no content type');
$joined = implode("\n", $get);
foreach (['fictional-secret', 'Bearer', 'Cookie', 'Referer', '10.10.10.10', 'test.example.invalid', 'Authorization'] as $s) {
    check(!str_contains($joined, $s), 'drops ' . $s);
}
$post = CgmRelayForwardHeaderPolicy::select($server, 'POST');
check(in_array('Content-Type: application/x-www-form-urlencoded', $post, true), 'POST content type preserved');
check(count($post) === 5, 'POST count');
$head = CgmRelayForwardHeaderPolicy::select($server, 'HEAD');
check(count($head) === 4, 'HEAD count');
$control = $server;
$control['HTTP_USER_AGENT'] = "evil\r\nInjected: fake";
check(count(CgmRelayForwardHeaderPolicy::select($control,'GET'))===3,'rejects CRLF header injection');
$control['HTTP_USER_AGENT'] = "invalid\x7fua";
check(count(CgmRelayForwardHeaderPolicy::select($control,'GET'))===3,'rejects control character');
$control['HTTP_USER_AGENT'] = str_repeat('A',513);
check(count(CgmRelayForwardHeaderPolicy::select($control,'GET'))===3,'rejects oversized header');
$control['HTTP_USER_AGENT'] = "multibyte-\xc3\xa9";
check(count(CgmRelayForwardHeaderPolicy::select($control,'GET'))===3,'rejects nonascii header');
$control['HTTP_USER_AGENT'] = ['not-a-string'];
check(count(CgmRelayForwardHeaderPolicy::select($control,'GET'))===3,'rejects nonstring header');
$control['HTTP_USER_AGENT'] = '';
check(count(CgmRelayForwardHeaderPolicy::select($control,'GET'))===3,'skips empty header');
$empty = CgmRelayForwardHeaderPolicy::select([], 'GET');
check($empty === ['Accept-Encoding: identity','Connection: close'], 'only safe fixed headers when no client headers');
try { CgmRelayForwardHeaderPolicy::select($server, 'TRACE'); $bad = false; }
catch (InvalidArgumentException $e) { $bad = true; }
check($bad, 'rejects unsupported method');
$audit = CgmRelayForwardHeaderPolicy::audit($server,'GET');
check($audit['classification']==='STAGING_HEADER_REDUCTION_ONLY','audit classification');
check($audit['header_count'] === 4, 'audit count');
foreach (['provider_permission_verified','official_identity_authenticated','game_session_authorized','live_forwarding_enabled'] as $field) {
    check($audit[$field]===false, $field . ' false');
}
$plain=json_encode($audit,JSON_THROW_ON_ERROR);
check(!str_contains($plain,'fictional-secret'),'audit no sensitive headers');
echo "ALL {$n}/{$n} PASS\n";
