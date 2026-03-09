<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Data;

final readonly class TemplateCacheContext
{
    public function __construct(
        public string $cacheKey,
        public bool $global,
        public bool $resources,
    ) {}
}
