<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/yii2-adapter/lib',
        __DIR__ . '/yii2-adapter/legacy',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/yii2-adapter/legacy-tests/functional',
        __DIR__ . '/yii2-adapter/legacy-tests/unit',
    ])
    ->withSkip([
        __DIR__ . '/resources/icons/index.php',
        __DIR__ . '/resources/icons/aliases.php',

        // somehow craft\web AssetManager refer with Yii parent AssetManager class
        // autoload may need to be bootstrapped to early load some child classes
        RemoveExtraParametersRector::class,

        // macro usage, make phpstan notice
        ClosureToArrowFunctionRector::class => [
            __DIR__ . '/yii2-adapter/legacy/base/ApplicationTrait.php',
        ],
    ])
    ->withPhpSets(php74: true);
