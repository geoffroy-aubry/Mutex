<?php

require __DIR__ . '/../vendor/autoload.php';

use GAubry\Logger\MinimalLogger;
use GAubry\Mutex\Semaphore;

date_default_timezone_set('UTC');
$oLogger = new MinimalLogger();
$oSem = new Semaphore($oLogger, 2, 100, '/tmp/demo-sem');

$oSem->acquire();
echo "Do anything for 3 seconds…\n";
sleep(3);
$oSem->release();

/*
RESULT for 3 processes P1, P2 and P3:

P1 $ php examples/demo-semaphore.php
Do anything for 3 seconds…

P2 $ php examples/demo-semaphore.php
Do anything for 3 seconds…

P3 $ php examples/demo-semaphore.php
Waiting to acquire lock on /tmp/demo-sem…
Lock acquired after 2.30s
Do anything for 3 seconds…

*/
