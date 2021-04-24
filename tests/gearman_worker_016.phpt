--TEST--
GearmanWorker::addFunction(), context param
--SKIPIF--
<?php if (!extension_loaded("gearman")) print "skip";
require_once('skipifconnect.inc');
?>
--FILE--
<?php
$host = 'localhost';
$port = 4730;
$job = 'AddFunctionArrCtxTest';
$jobWithScalarByRefCtx = 'AddFunctionScalarByRefCtxTest';
$workload = '{"workload":"test"}';

$counter = 0;

$client = new GearmanClient();
$client->addServer($host, $port);
$handle = $client->doBackground($job, $workload);
$client->doBackground($job, $workload);
$client->doBackground($job, $workload);
$client->doBackground($job, $workload);
$client->doBackground($jobWithScalarByRefCtx, $workload);
$client->doBackground($jobWithScalarByRefCtx, $workload);
$client->doBackground($jobWithScalarByRefCtx, $workload);

print "GearmanWorker::doBackground() (OO): ".(preg_match('/^H:'.gethostname().':\d+$/', $handle) === 1? 'Success' : 'Failure').PHP_EOL;

$worker = new TestWorker();
print "GearmanWorker::work() (OO, array ctx): " .($worker->work() === true ? 'Success' : 'Failure') . PHP_EOL;
print "GearmanWorker::work() (OO, array ctx): " .($worker->work() === true ? 'Success' : 'Failure') . PHP_EOL;
print "GearmanWorker::work() (OO, array ctx): " .($worker->work() === true ? 'Success' : 'Failure') . PHP_EOL;

$worker = new TestWorkerWithContextModification();
print "GearmanWorker::work() (OO, scalar by ref ctx): " .($worker->work() === true ? 'Success' : 'Failure') . PHP_EOL;
print "GearmanWorker::work() (OO, scalar by ref ctx): " .($worker->work() === true ? 'Success' : 'Failure') . PHP_EOL;
print "GearmanWorker::work() (OO, scalar by ref ctx): " .($worker->work() === true ? 'Success' : 'Failure') . PHP_EOL;


print "OK";

class TestWorker extends \GearmanWorker
{
    public function __construct()
    {
	global $job;
        parent::__construct();
        $this->addServer();
        $this->addFunction($job, [$this, 'test'], ['firstArg' => 'firstValue']);
    }

    public function test($job, $context)
    {
        echo "Starting job {$job->workload()}". PHP_EOL;
        $firstArg = $context['firstArg'];
        echo "FirstArg is $firstArg" . PHP_EOL;
    }
}

class TestWorkerWithContextModification extends \GearmanWorker
{
    public function __construct()
    {
        global $jobWithScalarByRefCtx, $counter;
        parent::__construct();

        $this->addServer();
        $this->addFunction($jobWithScalarByRefCtx, [$this, 'test'], $counter);
    }

    public function test($job, &$context)
    {
        echo "Starting job {$job->workload()}". PHP_EOL;
        $context++;
        echo "Counter is $context" . PHP_EOL;
    }
}

?>
--EXPECT--
GearmanWorker::doBackground() (OO): Success
Starting job {"workload":"test"}
FirstArg is firstValue
GearmanWorker::work() (OO, array ctx): Success
Starting job {"workload":"test"}
FirstArg is firstValue
GearmanWorker::work() (OO, array ctx): Success
Starting job {"workload":"test"}
FirstArg is firstValue
GearmanWorker::work() (OO, array ctx): Success
Starting job {"workload":"test"}
Counter is 1
GearmanWorker::work() (OO, scalar by ref ctx): Success
Starting job {"workload":"test"}
Counter is 2
GearmanWorker::work() (OO, scalar by ref ctx): Success
Starting job {"workload":"test"}
Counter is 3
GearmanWorker::work() (OO, scalar by ref ctx): Success
OK
