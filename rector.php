<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/src',
        __DIR__ . '/tests/functional',
        __DIR__ . '/tests/unit',
    ])
    ->withSkip([
        __DIR__ . '/src/icons/index.php',

        RandomFunctionRector::class => [
            __DIR__ . '/src/helpers/StringHelper.php',
        ],
    ])
    ->withPhpSets(php70: true);
