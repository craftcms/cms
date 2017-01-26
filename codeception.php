<?php

require_once dirname(__FILE__).'/vendor/codeception/codeception/autoload.php';

use Symfony\Component\Console\Application;

$app = new Application('Codeception', Codeception\Codecept::VERSION);
$app->add(new Codeception\Command\Run('run'));

$app->run();