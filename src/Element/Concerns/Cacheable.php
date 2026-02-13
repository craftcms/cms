<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Element\Events\DefineCacheTags;

/**
 * Cacheable provides cache tag management for elements.
 *
 * This trait contains methods for defining and retrieving cache tags that should be
 * invalidated when an element is saved, with support for customization via events.
 *
 * @internal
 */
trait Cacheable
{
    /**
     * Returns the cache tags that should be cleared when this element is saved.
     *
     * @return string[]
     *
     * @since 3.5.0
     */
    public function getCacheTags(): array
    {
        event($event = new DefineCacheTags($this, $this->cacheTags()));

        return $event->tags;
    }

    /**
     * Returns the cache tags that should be cleared when this element is saved.
     *
     * @return string[]
     *
     * @since 4.1.0
     */
    protected function cacheTags(): array
    {
        return [];
    }
}
