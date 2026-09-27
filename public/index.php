<?php
require __DIR__ . '/_common.php';
out([
  'ok' => true,
  'service' => 'JPCG VIP Test API',
  'endpoints' => ['/health.php', '/redeemvip.php?data=JPANL#########', '/verify.php?data=JPANL#########']
]);
