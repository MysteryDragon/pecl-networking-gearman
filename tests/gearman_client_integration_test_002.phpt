--TEST--
GearmanClient::setStatusCallback(), gearman_client_set_status_callback(),
GearmanClient::addTaskStatus(), gearman_client_add_task_status(),
GearmanClient::runTasks(), gearman_client_run_tasks()
--SKIPIF--
<?php if (!extension_loaded("gearman")) print "skip";
require_once('skipifconnect.inc');
?>
--FILE--
<?php
function reverse_status($task, $context)
{
	print "In " . __FUNCTION__
		. " context is '$context'"
		. PHP_EOL;
}

$client = new GearmanClient();
$client->addServer('localhost');

$handle = $client->doBackground("reverse", "Hello World!");

$client->setStatusCallback("reverse_status");

$oo_context = "context passed in through OO";

$client->addTaskStatus($handle, $oo_context);

// Should print within reverse_status
$client->runTasks();

$client2 = gearman_client_create();
gearman_client_add_server($client2, 'localhost', 4730);

$handle = gearman_client_do_background($client2, "reverse", "Hello World!");

gearman_client_set_status_callback($client2, "reverse_status");

$procedural_context = "context passed in through procedural";

gearman_client_add_task_status($client2, $handle, $procedural_context);

gearman_client_run_tasks($client2);

/* Testing OO way with scalar context, passed by reference */

function increase_counter($task, &$context)
{
    $context++;

    // We won't receive $context by reference, and there it will be always 1
	print "In " . __FUNCTION__
		. " context is $context"
		. PHP_EOL;
}

$clientForScalarByRefCtx = new GearmanClient();
$clientForScalarByRefCtx->addServer('localhost');

$counter = 0;

$clientForScalarByRefCtx->setStatusCallback("increase_counter");

for ($i = 0; $i < 2; $i++) {
    $handle = $clientForScalarByRefCtx->doBackground("reverse", "Hello World!");

    $clientForScalarByRefCtx->addTaskStatus($handle, $counter);

    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000) {
        // Worker function for context testing will throw warning about $context that must be passed
        // by reference instead of by value
        $error_reporting_value = ini_get('error_reporting');
        ini_set('error_reporting', $error_reporting_value & ~E_WARNING);
    }

    // Should print within increase_counter
    $clientForScalarByRefCtx->runTasks();

    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000) {
        ini_set('error_reporting', $error_reporting_value);
    }
}

print "OK";
?>
--EXPECT--
In reverse_status context is 'context passed in through OO'
In reverse_status context is 'context passed in through procedural'
In increase_counter context is 1
In increase_counter context is 1
OK
