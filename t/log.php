<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use \kcmerrill\utility\snitchin as snitchin;

$log = new snitchin($argv,'file|standard');
$log->snitcher('file','/tmp/snitchin.txt', 1);
$log->info('This should be in /tmp/snitchin.txt');
