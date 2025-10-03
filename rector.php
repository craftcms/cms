<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/resources/icons/index.php',
        __DIR__.'/resources/icons/aliases.php',

        // somehow craft\web AssetManager refer with Yii parent AssetManager class
        // autoload may need to be bootstrapped to early load some child classes
        RemoveExtraParametersRector::class,
    ])
    ->withPhpSets(php74: true);
