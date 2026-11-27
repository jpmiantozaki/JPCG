<?php
declare(strict_types=1);
// Staging-only replacement for the legacy db helper. No legacy DDL.
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $url=getenv('DATABASE_URL')?:'';
    $expected=getenv('CGM_SOLO_STAGE_DB_HOST')?:'';
    $p=parse_url($url);
    if (!$p || ($p['scheme']??'')!=='postgresql' ||
        $expected==='' || ($p['host']??'')!==$expected ||
        !preg_match('/\A[a-z0-9.-]+\.neon\.tech\z/D',$expected) ||
        ($p['path']??'')!=='/cgm_solo_stage_r43159' ||
        (int)($p['port']??5432)!==5432 ||
        rawurldecode($p['user']??'')!=='solo_stage_r43159_app' ||
        empty($p['pass'])) throw new RuntimeException('staging database configuration rejected');
    $dsn='pgsql:host='.$expected.';port=5432;dbname=cgm_solo_stage_r43159;sslmode=verify-full;sslrootcert=/etc/ssl/certs/ca-certificates.crt;connect_timeout=10';
    $candidate=new PDO($dsn,rawurldecode($p['user']),rawurldecode($p['pass']),[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false
    ]);
    // PHP 8.3 with transaction pooling: avoid named PREPARE/DEALLOCATE.
    // Keep parameters bound separately through the native PostgreSQL driver.
    if (!$candidate->setAttribute(PDO::PGSQL_ATTR_DISABLE_PREPARES,true))
        throw new RuntimeException('pooler parameter mode unavailable');
    $r=$candidate->query('SELECT current_database() AS db, current_user AS role')->fetch();
    if ($r['db']!=='cgm_solo_stage_r43159' || $r['role']!=='solo_stage_r43159_app')
        throw new RuntimeException('staging database identity mismatch');
    $pdo=$candidate;
    return $pdo;
}
