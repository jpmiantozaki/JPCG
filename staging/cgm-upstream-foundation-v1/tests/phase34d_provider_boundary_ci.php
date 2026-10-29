<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/ProviderHandoffGate.php';
require_once __DIR__ . '/../src/GenerationBoundRelayLedger.php';
use CGM\Phase40CD\ProviderHandoffGate;
use CGM\Phase40CD\UnconfiguredProviderVerifier;
use CGM\Phase40CD\UnconfiguredAccountLinkRepository;
use CGM\Phase40CD\UnconfiguredMembershipRepository;
use CGM\Phase40CD\DenyOnlyAttemptStore;
use CGM\Phase34D\GenerationBoundRelayLedger;
$g = new ProviderHandoffGate(new UnconfiguredProviderVerifier(),
    new UnconfiguredAccountLinkRepository(),new UnconfiguredMembershipRepository(),
    new DenyOnlyAttemptStore(),'cgm-upstream-garden-v1');
$l = new GenerationBoundRelayLedger();
$now=100000;
$id=$l->begin($now);
$l->openSocket($id,'LOGIN',$now);
$r=$l->enqueue($id,0,'LOGIN',$now);
$v=$l->deliver($r['request_id'],$id,0,'LOGIN',1,$now);
if($v['local_callback_correlated']!==true || $v['official_game_authenticated']!==false)throw new RuntimeException('local-only verdict');
$d=$g->evaluate('fictional@example.test','fabricated-proof','fixture-attempt-1234567',$now);
if($d['decision']!=='DENIED' || $d['session_issued']!==false || $d['game_packet_generated']!==false)throw new RuntimeException('provider gate not fail-closed');
echo "PHASE34D_PROVIDER_BOUNDARY_CI_PASS checks=5 SYNTHETIC_ONLY\n";
