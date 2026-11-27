<?php
declare(strict_types=1);
require_once __DIR__.'/_solo_vip_api.php';
require_once __DIR__.'/_solo_vip_pg.php';
if (getenv('CGM_SOLO_VIP_ENABLED')!=='1') solo_reply(['error'=>'service_disabled'],503);
require_once __DIR__.'/_common.php';
solo_dispatch(static fn()=>new SoloPgStore(db()),getenv('JPCG_ADMIN_KEY')?:'',true);
