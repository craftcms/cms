<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\events\DefineValueEvent;

/**
 * @mixin \CraftCms\Cms\Element\Element
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
        $cacheTags = $this->cacheTags();

        // Fire a 'defineCacheTags' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_CACHE_TAGS)) {
            $event = new DefineValueEvent(['value' => $cacheTags]);
            $this->trigger(self::EVENT_DEFINE_CACHE_TAGS, $event);

            return $event->value;
        }

        return $cacheTags;
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
