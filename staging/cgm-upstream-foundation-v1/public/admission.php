<?php
declare(strict_types=1);
// STAGING ONLY. Never treat this endpoint as a game session issuer.
// This deployment has NO official provider verifier and always fails closed.
use CGM\Phase40CD\ProviderHandoffGate;
use CGM\Phase40CD\UnconfiguredProviderVerifier;
use CGM\Phase40CD\UnconfiguredAccountLinkRepository;
use CGM\Phase40CD\UnconfiguredMembershipRepository;
use CGM\Phase40CD\DenyOnlyAttemptStore;
use CGM\UpstreamV4\AdmissionApi;
require_once __DIR__ . '/../src/AdmissionApi.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$gate = new ProviderHandoffGate(
    new UnconfiguredProviderVerifier(),
    new UnconfiguredAccountLinkRepository(),
    new UnconfiguredMembershipRepository(),
    new DenyOnlyAttemptStore(),
    'cgm-upstream-garden-v1'
);
$api = new AdmissionApi($gate);
$body = file_get_contents('php://input', false, null, 0, 8193);
$result = $api->handle($_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['CONTENT_TYPE'] ?? '', is_string($body) ? $body : '', time());
http_response_code($result['http_status']);
if ($result['http_status'] === 405) header('Allow: POST');
echo json_encode($result['payload'], JSON_THROW_ON_ERROR);
