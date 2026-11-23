<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\CacheCollectors;

use CraftCms\Cms\HttpCache\ResponseTagger;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\TemplateCacheContext;

/**
 * Stores the tags added inside a `{% cache %}` block with the block, and adds them again on a cache hit.
 *
 * Element tags already replay through `DependencyCollector`; this covers tags added with `ResponseTagger`.
 *
 * @since 6.0.0
 */
readonly class ResponseTagCollector implements CacheCollectorInterface
{
    public function __construct(
        private ResponseTagger $tagger,
    ) {}

    public static function key(): string
    {
        return 'responseTags';
    }

    public function begin(TemplateCacheContext $context): void
    {
        $this->tagger->startCapture();
    }

    /**
     * @return list<string>
     */
    public function end(TemplateCacheContext $context): array
    {
        return $this->tagger->stopCapture();
    }

    public function apply(mixed $payload, TemplateCacheContext $context): void
    {
        if (is_array($payload)) {
            $this->tagger->addTags(array_values(array_filter($payload, is_string(...))));
        }
    }
}
