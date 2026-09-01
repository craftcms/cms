<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\CacheCollectors\ResourceCollector;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers collectors that contribute dependencies to template caches.
 *
 * ```php
 * public function boot(TemplateCacheCollectors $collectors): void
 * {
 *     $collectors->register(MyCacheCollector::class);
 * }
 * ```
 *
 * @extends TypeRegistry<CacheCollectorInterface>
 */
#[Singleton]
class TemplateCacheCollectors extends TypeRegistry
{
    protected const string CONTRACT = CacheCollectorInterface::class;

    protected const array DEFAULT_TYPES = [
        DependencyCollector::class,
        ResourceCollector::class,
    ];

    protected const array PROTECTED_TYPES = self::DEFAULT_TYPES;

    /** @param class-string<CacheCollectorInterface> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return $type::key();
    }
}
