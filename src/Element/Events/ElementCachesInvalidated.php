<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCaches;

/**
 * Fired when Craft invalidates the caches tagged with any of {@see $tags}. The tags can be element tags, such as
 * `element::5`, or custom tags invalidated with {@see ElementCaches::invalidateTags()}.
 *
 * Inside a database transaction, the event can fire before the caches are cleared, which happens once it commits.
 * Listeners that purge other caches, such as HTTP caches, should send their purges when the request or job ends,
 * as `craftcms/http-cache` does, so a purged cache isn't refilled with content from before the commit.
 *
 * @since 6.0.0
 */
class ElementCachesInvalidated
{
    public function __construct(
        /**
         * @var string[] The tags being invalidated
         */
        public array $tags,

        /**
         * @var ElementInterface|null The element whose caches are being invalidated, if this was triggered from {@see ElementCaches::invalidateForElement()}.
         */
        public ?ElementInterface $element = null,
    ) {}
}
