<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Data;

/**
 * @since 6.0.0
 */
readonly class TemplateCacheContext
{
    public function __construct(
        public string $cacheKey,
        public bool $global,
        public bool $resources,
    ) {}
}
