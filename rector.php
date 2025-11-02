<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use RectorLaravel\Rector\ArrayDimFetch\EnvVariableToEnvHelperRector;
use RectorLaravel\Rector\Class_\AnonymousMigrationsRector;
use RectorLaravel\Rector\MethodCall\ResponseHelperCallToJsonResponseRector;
use RectorLaravel\Rector\MethodCall\UseComponentPropertyWithinCommandsRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/resources/icons/index.php',
        __DIR__.'/resources/icons/aliases.php',
        AnonymousMigrationsRector::class => [
            __DIR__.'/src/Database/Migrations/BaseContentRefactorMigration.php',
            __DIR__.'/src/Database/Migrations/BaseEntryTypeMergeMigration.php',
            __DIR__.'/src/Database/Migrations/BaseFieldMergeMigration.php',
            __DIR__.'/src/Database/Migrations/Install.php',
        ],
        EnvVariableToEnvHelperRector::class => [
            __DIR__.'/src/Utility/Utilities/PhpInfo.php',
        ],
    ])
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)
    ->withSets([
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_TESTING,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    ])
    ->withRules([
        DeclareStrictTypesRector::class,
        ResponseHelperCallToJsonResponseRector::class,
        UseComponentPropertyWithinCommandsRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withPhpSets(php84: true);
