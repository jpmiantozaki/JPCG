<?php
declare(strict_types=1);
// Only runs against an explicitly provisioned test database (e.g. CI PG service).
require_once __DIR__ . '/../src/PgAtomicAttemptStore.php';
use CGM\UpstreamV4\PgAtomicAttemptStore;

$dsn=getenv('CGM_TEST_PG_DSN') ?: '';
$user=getenv('CGM_TEST_PG_USER') ?: '';
$pass=getenv('CGM_TEST_PG_PASS') ?: '';
if ($dsn === '' || strpos($dsn,'pgsql:') !== 0) {
    fwrite(STDERR,"BLOCKED: CGM_TEST_PG_DSN must target a disposable pgsql test DB.\n");
    exit(2);
}
// Deliberately do not log DSN, user, password, or query values.
function checkIt(bool $b,string $name):void{global $checks;$checks++;if(!$b){throw new RuntimeException("FAIL: $name");}echo "PASS $name\n";}
$checks=0;
$options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false];
$db1=new PDO($dsn,$user,$pass,$options);
$db2=new PDO($dsn,$user,$pass,$options);
$sql=file_get_contents(__DIR__.'/../sql/001_cgm_upstream_admission_attempts.sql');
$db1->exec($sql);
$db1->exec('TRUNCATE cgm_upstream_admission_attempts');
$secret=str_repeat('synthetic-ci-key-',4);
$a=new PgAtomicAttemptStore($db1,$secret);
$b=new PgAtomicAttemptStore($db2,$secret);
$now=time();
checkIt($a->consume('fixture-issuer','fixture-subject','unique-test-AAA1234567',$now+120),'first consumption committed');
checkIt(!$b->consume('fixture-issuer','fixture-subject','unique-test-AAA1234567',$now+120),'second connection cannot replay');
checkIt(!$b->consume('other-issuer','other-subject','unique-test-AAA1234567',$now+120),'attemptId globally unique across subjects');
checkIt(!$a->consume('fixture-issuer','fixture-subject','unique-test-BBB1234567',$now-1),'expired grant denied by database clock');
checkIt(!$a->consume('fixture-issuer','fixture-subject','short',$now+120),'invalid attempt identifier denied');
checkIt($b->consume('fixture-issuer','fixture-subject','unique-test-CCC1234567',$now+120),'independent attempt accepted');
$count=(int)$db1->query('SELECT COUNT(*) FROM cgm_upstream_admission_attempts')->fetchColumn();
checkIt($count===2,'only two unique claims persisted');
$rows=$db1->query('SELECT attempt_digest,valid_until FROM cgm_upstream_admission_attempts ORDER BY attempt_digest')->fetchAll(PDO::FETCH_ASSOC);
checkIt(count($rows)===2,'two row results');
foreach($rows as $row){
    checkIt((bool)preg_match('/\A[a-f0-9]{64}\z/', $row['attempt_digest']),'persisted digest format');
    checkIt($row['attempt_digest']!=='unique-test-AAA1234567','raw attempt ID never persisted');
}
$columns=$db1->query("SELECT column_name FROM information_schema.columns WHERE table_name='cgm_upstream_admission_attempts'")->fetchAll(PDO::FETCH_COLUMN);
checkIt($columns===['valid_until','created_at','attempt_digest'] || (count($columns)===3 && in_array('attempt_digest',$columns,true)),'no account/credential columns in ledger');
$db1->exec('TRUNCATE cgm_upstream_admission_attempts');
echo "CGM_PHASE30_POSTGRES_ATOMIC_PASS assertions=$checks (EPHEMERAL DB ONLY)\n";
