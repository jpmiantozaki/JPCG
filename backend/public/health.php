<?php
require __DIR__.'/_common.php';
try {
    $db=db();
    $db->query('SELECT 1')->fetchColumn();
    respond(['ok'=>true,'service'=>'jpcg-vip-service-v2','database'=>'postgresql','db_ok'=>true,'time_utc'=>gmdate('c')]);
} catch(Throwable $e) {
    error_log('[JPCG_HEALTH_DB_ERROR] '.$e->getMessage());
    respond(['ok'=>false,'service'=>'jpcg-vip-service-v2','database'=>'postgresql','db_ok'=>false,'time_utc'=>gmdate('c')],500);
}
