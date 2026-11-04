<?php
declare(strict_types=1);
// Static, offline check of the staging Docker/Apache configuration.
// No endpoints or actual login data are used.
$root = dirname(__DIR__);
$vhost = (string)file_get_contents($root . '/apache-cgm-vhost.conf');
$docker = (string)file_get_contents($root . '/Dockerfile.staging');
$count = 0;
function t(bool $condition, string $name): void {
    global $count;
    if (!$condition) { fwrite(STDERR, "FAIL $name\n"); exit(1); }
    $count++;
}
t(str_contains($vhost, 'CustomLog /proc/self/fd/1 cgm_path_only'), 'replaces default log format');
t(str_contains($vhost, '%U'), 'path only token');
t(!preg_match('/(?<!%)%r\b|%q\b|%\{[^}]+\}i|%a\b|%h\b|%u\b/', preg_replace('/^\s*#.*$/m', '', $vhost)), 'no unsafe logging directives');
t(!str_contains($vhost, ' combined'), 'no combined access log');
t(str_contains($docker, 'COPY staging/cgm-relay-privacy-v1/apache-cgm-vhost.conf /etc/apache2/sites-available/000-default.conf'), 'overwrites original virtual host log');
t(str_contains($docker, 'rm -f /etc/apache2/conf-enabled/other-vhosts-access-log.conf'), 'disables second full request logger');
t(str_contains($docker, 'apache2ctl configtest'), 'performs build-time apache syntax check');
t(str_contains($docker, 'COPY public/ /var/www/html/'), 'preserves original application files');
t(!str_contains($docker, 'COPY staging/cgm-independent-relay-hypothesis-v1/src/ZeroTransformRelayCandidate.php'), 'does not deploy candidate session issuer');
echo "ALL $count/$count PASS (static configuration only)\n";
