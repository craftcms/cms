<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Property\RemoveDefaultValueFromAssignedPropertyRector;
use Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use RectorLaravel\Rector\ArrayDimFetch\EnvVariableToEnvHelperRector;
use RectorLaravel\Rector\Class_\AnonymousMigrationsRector;
use RectorLaravel\Rector\FuncCall\AppToResolveRector;
use RectorLaravel\Rector\MethodCall\ResponseHelperCallToJsonResponseRector;
use RectorLaravel\Rector\MethodCall\UseComponentPropertyWithinCommandsRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withCache(
        // ensure file system caching is used instead of in-memory
        cacheDirectory: '/tmp/rector',
        // specify a path that works locally as well as on CI job runners
        cacheClass: FileCacheStorage::class
    )
    ->withSkip([
        AnonymousMigrationsRector::class => [
            __DIR__.'/src/Database/Migrations/BaseContentRefactorMigration.php',
            __DIR__.'/src/Database/Migrations/BaseEntryTypeMergeMigration.php',
            __DIR__.'/src/Database/Migrations/BaseFieldMergeMigration.php',
            __DIR__.'/src/Database/Migrations/Install.php',
        ],
        EnvVariableToEnvHelperRector::class => [
            __DIR__.'/src/Utility/Utilities/PhpInfo.php',
        ],
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class => [
            __DIR__.'/src/Http/Mixins/SessionMixin.php',
            // the arrow function is rebound via ->call($field, ...) below, so $this->searchKeywords()
            // must stay lazily resolved rather than being turned into a first-class callable bound to the test case
            __DIR__.'/tests/Feature/Field/MarkdownFieldTest.php',
        ],
        ArrayToFirstClassCallableRector::class => [
            // the array callable is deliberately not a Closure, to test that non-Closure callables are rejected
            __DIR__.'/tests/Unit/Twig/Extensions/ArrayTwigExtensionTest.php',
        ],
        AppToResolveRector::class,
        RemoveDefaultValueFromAssignedPropertyRector::class,
        RemoveNullPropertyInitializationRector::class,
        StringClassNameToClassConstantRector::class => [
            __DIR__.'/src/Console/PromptTask.php',
        ],
    ])
    ->withSets([
        LaravelSetList::LARAVEL_130_WITHOUT_ATTRIBUTES,
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
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
    ->withPhpSets(php85: true);
