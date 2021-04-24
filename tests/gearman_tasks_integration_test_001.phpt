--TEST--
Test Gearman worker methods
--SKIPIF--
<?php
require_once('skipif.inc');
require_once('skipifconnect.inc');
?>
--FILE--
<?php
require_once('connect.inc');

print "Start" . PHP_EOL;

$job_name = uniqid();
$job_name_for_context = uniqid();

$counter = 0;

$pid = pcntl_fork();
if ($pid == -1) {
    die("Could not fork");
} else if ($pid > 0) {
    // Parent. This is the worker
    $worker = new GearmanWorker();
    print "addServer: " . var_export($worker->addServer($host, $port), true) . PHP_EOL;
    print "addFunction: " . var_export(
        $worker->addFunction(
            $job_name,
            function($job) {
                print "workload: " . var_export($job->workload(), true) . PHP_EOL;
            }
        ),
        true
    ) . PHP_EOL;
    print "addFunction (for context): " . var_export(
        $worker->addFunction(
            $job_name_for_context,
            function($job, &$context) {
                $context++;
                // We won't receive $context by reference, and there it will be always 1
                print "context: " . var_export($context, true) . PHP_EOL;
            }
        ),
        true
    ) . PHP_EOL;

    for ($i = 0; $i < 12; $i++) {
        $worker->work();
    }

    print "unregister: " . var_export($worker->unregister($job_name), true) . PHP_EOL;
    print "unregister (for context): " . var_export($worker->unregister($job_name_for_context), true) . PHP_EOL;

    // Wait for child
    $exit_status = 0;
    if (pcntl_wait($exit_status) <= 0) {
        print "pcntl_wait exited with error" . PHP_EOL;
    } else if (!pcntl_wifexited($exit_status)) {
        print "child exited with error" . PHP_EOL;
    }
} else {
    //Child. This is the client. Don't echo anything here
    $client = new GearmanClient();
    if ($client->addServer($host, $port) !== true) {
        exit(1); // error
    };

    $tasks = [];
    $tasks[] = $client->addTask($job_name, "normal");
    $tasks[] = $client->addTaskBackground($job_name, "normalbg");
    $tasks[] = $client->addTaskHigh($job_name, 1);
    $tasks[] = $client->addTaskHighBackground($job_name, 2.0);
    $tasks[] = $client->addTaskLow($job_name, "low");
    $tasks[] = $client->addTaskLowBackground($job_name, true);
    $tasks[] = $client->addTask($job_name_for_context, "test", $counter);
    $tasks[] = $client->addTaskBackground($job_name_for_context, "test", $counter);
    $tasks[] = $client->addTaskHigh($job_name_for_context, "test", $counter);
    $tasks[] = $client->addTaskHighBackground($job_name_for_context, "test", $counter);
    $tasks[] = $client->addTaskLow($job_name_for_context, "test", $counter);
    $tasks[] = $client->addTaskLowBackground($job_name_for_context, "test", $counter);
    $client->runTasks();
    if ($client->returnCode() != GEARMAN_SUCCESS) {
        exit(2); // error
    }
    exit(0);
}

print "Done";
--EXPECTF--
Start
addServer: true
addFunction: true
addFunction (for context): true
workload: '2'
workload: '1'
workload: 'normalbg'
workload: 'normal'
workload: '1'
workload: 'low'
context: 1
context: 1
context: 1
context: 1
context: 1
context: 1
unregister: true
unregister (for context): true
Done
