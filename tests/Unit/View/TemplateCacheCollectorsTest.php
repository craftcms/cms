<?php

declare(strict_types=1);

use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\CacheCollectors\ResourceCollector;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\Cms\View\TemplateCacheCollectors;

it('contains its core collectors', function () {
    expect(app(TemplateCacheCollectors::class)->types())
        ->toContain(DependencyCollector::class, ResourceCollector::class);
});

it('rejects collectors that do not implement the contract', function () {
    expect(fn () => app(TemplateCacheCollectors::class)->register(stdClass::class))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects duplicate collector keys without partially registering the batch', function () {
    $registry = app(TemplateCacheCollectors::class);

    expect(fn () => $registry->register(AnotherTemplateCacheCollector::class, DuplicateKeyTemplateCacheCollector::class))
        ->toThrow(InvalidArgumentException::class)
        ->and($registry->types())
        ->not()->toContain(AnotherTemplateCacheCollector::class, DuplicateKeyTemplateCacheCollector::class);
});

class LazyTemplateCacheCollector implements CacheCollectorInterface
{
    public static function key(): string
    {
        return 'lazy';
    }

    public function begin(TemplateCacheContext $context): void {}

    public function end(TemplateCacheContext $context): mixed
    {
        return null;
    }

    public function apply(mixed $payload, TemplateCacheContext $context): void {}
}

class AnotherTemplateCacheCollector extends LazyTemplateCacheCollector
{
    #[Override]
    public static function key(): string
    {
        return 'another';
    }
}

class DuplicateKeyTemplateCacheCollector extends AnotherTemplateCacheCollector {}
