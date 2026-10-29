<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/Ed25519ProviderVerifier.php';
require_once __DIR__ . '/../src/PinnedProviderVerifierFactory.php';
use CGM\Phase34F\Ed25519ProviderVerifier;
use CGM\Phase34F\PinnedProviderVerifierFactory;
use CGM\Phase40CD\UnconfiguredProviderVerifier;
function b64(string $x):string{return rtrim(strtr(base64_encode($x),'+/','-_'),'=');}
function check(bool $yes,string $label):void{if(!$yes)throw new RuntimeException('FAIL '.$label);}
$pair=sodium_crypto_sign_keypair();
$pub=sodium_crypto_sign_publickey($pair);
$priv=sodium_crypto_sign_secretkey($pair);
$now=100000;
$header=['alg'=>'EdDSA','typ'=>'JWT','kid'=>'synthetic-key'];
$claims=['iss'=>'urn:synthetic:operator','sub'=>'fixture-123','email'=>'fixture@example.invalid',
'aud'=>'cgm-upstream-garden-v1','attempt_id'=>'fixture-attempt-123456','iat'=>$now,
'nbf'=>$now,'exp'=>$now+60,'game_authenticated'=>true,'garden_authorized'=>true,
'intermediary_entitlement_accepted'=>true];
$h=b64(json_encode($header,JSON_THROW_ON_ERROR));
$p=b64(json_encode($claims,JSON_THROW_ON_ERROR));
$input=$h.'.'.$p;
$proof=$input.'.'.b64(sodium_crypto_sign_detached($input,$priv));
$v=new Ed25519ProviderVerifier('urn:synthetic:operator','cgm-upstream-garden-v1',['synthetic-key'=>$pub]);
$accepted=$v->verify($proof,$now);
check($accepted!==null,'real signature verified');
check($accepted->boundAttemptId===$claims['attempt_id'],'exact attempt bound');
check($accepted->officialGardenAuthorized===true,'explicit attested Garden');
check($v->verify($proof.'.garbage',$now)===null,'malformed proof rejected');
check($v->verify($proof,$now+121)===null,'expired proof rejected');
$mutated=$h.'.'.b64(json_encode(array_replace($claims,['garden_authorized'=>false]),JSON_THROW_ON_ERROR));
$mutated.='.'.b64(sodium_crypto_sign_detached($mutated,$priv));
check($v->verify($mutated,$now)===null,'explicit denied Garden rejected');
$factory=PinnedProviderVerifierFactory::fromEnvironment([
'CGM_TRUSTED_PROVIDER_ISSUER'=>'urn:synthetic:operator',
'CGM_TRUSTED_PROVIDER_AUDIENCE'=>'cgm-upstream-garden-v1',
'CGM_TRUSTED_PROVIDER_KID'=>'synthetic-key',
'CGM_TRUSTED_PROVIDER_PUBKEY_B64URL'=>b64($pub)]);
check($factory->verify($proof,$now)!==null,'server pinned configuration');
check(PinnedProviderVerifierFactory::fromEnvironment([]) instanceof UnconfiguredProviderVerifier,'default denies');
echo "PHASE34F_SIGNED_PROVIDER_CI_PASS checks=8 SYNTHETIC_ONLY\n";
