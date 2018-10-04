<?php
// Here you can initialize variables that will be available to your tests

// Autoload the mock classes
\Codeception\Util\Autoload::addNamespace('', dirname(__DIR__).'/_support/mockclasses/components');
\Codeception\Util\Autoload::addNamespace('', dirname(__DIR__).'/_support/mockclasses/serializable');

\Codeception\Util\Autoload::addNamespace('', dirname(__DIR__).'/_support/Helper');
