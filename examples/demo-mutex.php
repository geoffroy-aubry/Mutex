<?php

require __DIR__ . '/../vendor/autoload.php';

use GAubry\Logger\MinimalLogger;
use GAubry\Mutex\Mutex;

$oLogger = new MinimalLogger();
$oMutex = new Mutex($oLogger, 100, '/tmp/demo-lock');

$oMutex->acquire();
echo "Do anything for 3 seconds…\n";
sleep(3);
$oMutex->release();

/*
RESULT for 2 processes P1 and P2:

P1 $ php examples/demo-mutex.php
Do anything for 3 seconds…

P2 $ php examples/demo-mutex.php
Waiting to acquire Mutex lock on /tmp/demo-lock…
Mutex lock acquired after 2.57s
Do anything for 3 seconds…

*/
