<?php

require __DIR__ . "/vendor/autoload.php";

$cli = new crodas\cli\Cli;
$cli->addDirectory(__DIR__ . '/src/');
$cli->main();
