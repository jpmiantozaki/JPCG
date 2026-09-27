<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true,'service'=>'jpcg-mock-vip-protocol','time_utc'=>gmdate('c')]);
