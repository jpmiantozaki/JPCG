<?php
declare(strict_types=1);
require __DIR__.'/_common.php';

// CGM v0.5D NON-VIP diagnostic only.
// data1/data2 retain the base APK's existing analytics shape for this test.
// Purpose: prove the known RelayServerManager.<OnPacketReceived>b__3 non-VIP callback
// can invoke the observer transport without altering ReceiveNoVIPData or membership logic.
$raw = value('data1');
$hash = hash('sha256', $raw);
error_log('[CGM_V05D_NOVIP_DIAGNOSTIC] reached=1 data1_hash='.$hash.' data1_len='.strlen($raw));
respond(['ok'=>true,'state'=>'DIAGNOSTIC','reached'=>true,'data1_hash'=>$hash]);
