<?php

declare(strict_types=1);

use CraftCms\Cms\View\CpBootstrap;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\InternalAssets;

it('registers the cp bootstrap script side effect with a key', function () {
    $cpBootstrap = Mockery::mock(CpBootstrap::class);
    $cpBootstrap->shouldReceive('icons')
        ->once()
        ->andReturn(['gear']);
    $cpBootstrap->shouldReceive('craftData')
        ->once()
        ->with([], [])
        ->andReturn(['foo' => 'bar']);

    $htmlStack = app(HtmlStack::class);
    $htmlStack->clear();

    $internalAssets = instantiateWithoutConstructor(InternalAssets::class);

    setPrivateProperty($internalAssets, 'cpBootstrap', $cpBootstrap);
    setPrivateProperty($internalAssets, 'htmlStack', $htmlStack);
    setPrivateProperty($internalAssets, 'registeredBundles', []);
    setPrivateProperty($internalAssets, 'registeredJsFiles', []);

    invokePrivateMethod($internalAssets, 'applyBundleSideEffects', 'cp');

    expect($htmlStack->headHtml())
        ->toContain('window.Craft = Object.assign({translations: {}}, window.Craft || {}, {"foo":"bar"});');
});

function instantiateWithoutConstructor(string $class): object
{
    return new ReflectionClass($class)->newInstanceWithoutConstructor();
}

function setPrivateProperty(object $object, string $property, mixed $value): void
{
    $reflectionProperty = new ReflectionClass($object)->getProperty($property);
    $reflectionProperty->setValue($object, $value);
}

function invokePrivateMethod(object $object, string $method, mixed ...$arguments): mixed
{
    $reflectionMethod = new ReflectionMethod($object, $method);

    return $reflectionMethod->invoke($object, ...$arguments);
}
