<?php
declare(strict_types=1);
require __DIR__.'/_common.php';

// v0.5E diagnostic endpoint. This does not grant VIP or alter membership.
// It only records whether the existing analytics transport was invoked from
// the ReceiveNoVIPData-entry diagnostic hook.
$raw = value('data1');
$hash = hash('sha256', $raw);
error_log('[CGM_V05E_RECEIVENOVIP_ENTRY] reached=1 data1_hash='.$hash.' data1_len='.strlen($raw));
respond([
    'ok' => true,
    'state' => 'DIAGNOSTIC',
    'reached' => true,
    'data1_hash' => $hash,
    'data1_len' => strlen($raw),
]);
