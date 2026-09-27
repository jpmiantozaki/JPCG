<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true,'service'=>'jpcg-vip-service-v1','time_utc'=>gmdate('c')]);
