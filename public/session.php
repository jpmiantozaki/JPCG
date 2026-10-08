<?php
declare(strict_types=1);
if (getenv('CGM_SESSION_DIAGNOSTICS') === '1') {
    require_once __DIR__ . '/_cgm_session_diagnostic.php';
    cgm_log_session_request();
}
require __DIR__ . '/_cgm_session_bridge.php';
cgm_session_bridge('session.php');
