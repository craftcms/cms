<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\CacheCollectors;

use CraftCms\Cms\Http\ResponseCacheInfo;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\TemplateCacheContext;

/**
 * Stores the tags added inside a `{% cache %}` block with the block, and adds them again on a cache hit.
 *
 * Element tags already replay through `DependencyCollector`; this covers tags added with `ResponseCacheInfo`.
 *
 * @since 6.0.0
 */
readonly class ResponseTagCollector implements CacheCollectorInterface
{
    public function __construct(
        private ResponseCacheInfo $cacheInfo,
    ) {}

    public static function key(): string
    {
        return 'responseTags';
    }

    public function begin(TemplateCacheContext $context): void
    {
        $this->cacheInfo->startCapture();
    }

    /**
     * @return list<string>
     */
    public function end(TemplateCacheContext $context): array
    {
        return $this->cacheInfo->stopCapture();
    }

    public function apply(mixed $payload, TemplateCacheContext $context): void
    {
        if (is_array($payload)) {
            $this->cacheInfo->addTags(array_values(array_filter($payload, is_string(...))));
        }
    }
}
