<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/src',
        __DIR__ . '/tests/functional',
        __DIR__ . '/tests/unit',
    ])
    ->withSkip([
        __DIR__ . '/src/icons/index.php',

        // somehow craft\web AssetManager refer with Yii parent AssetManager class
        // autoload may need to be bootstrapped to early load some child classes
        RemoveExtraParametersRector::class,

        // macro usage, make phpstan notice
        ClosureToArrowFunctionRector::class => [
            __DIR__ . '/src/base/ApplicationTrait.php',
        ],

        // used by finally and next if
        RemoveUnusedVariableInCatchRector::class => [
            __DIR__ . '/src/console/controllers/PluginController.php',
            __DIR__ . '/src/helpers/DateTimeHelper.php',
        ],


        ChangeSwitchToMatchRector::class => [
            // object check
            __DIR__ . '/src/elements/Entry.php',

            // by preference usage, no assign or returns used, just void call
            __DIR__ . '/src/base/NestedElementTrait.php',
            __DIR__ . '/src/elements/db/NestedElementQueryTrait.php',
            __DIR__ . '/src/console/controllers/ProjectConfigController.php',
            __DIR__ . '/src/elements/db/ElementQuery.php',
            __DIR__ . '/src/elements/db/EntryQuery.php',
            __DIR__ . '/src/i18n/Locale.php',
        ],

        MixedTypeRector::class,

        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/src/cache/ElementQueryTagDependency.php',
        ],
    ])
    ->withPhpSets(php80: true)
    ->withConfiguredRule(ClassPropertyAssignToConstructorPromotionRector::class, [
        ClassPropertyAssignToConstructorPromotionRector::RENAME_PROPERTY => false,
    ]);
