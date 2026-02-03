<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\events\DefineAttributeKeywordsEvent;
use CraftCms\Cms\Support\Str;

/**
 * @mixin \CraftCms\Cms\Element\Element
 *
 * @internal
 */
trait Searchable
{
    /**
     * @event DefineAttributeKeywordsEvent The event that is triggered when defining the search keywords for an
     * element attribute.
     *
     * Note that you _must_ set [[Event::$handled]] to `true` if you want the element to accept your custom
     * [[DefineAttributeKeywordsEvent::$keywords|$keywords]] value.
     *
     * ```php
     * Event::on(
     *     craft\elements\Entry::class,
     *     craft\base\Element::EVENT_DEFINE_KEYWORDS,
     *     function(craft\events\DefineAttributeKeywordsEvent $e
     * ) {
     *     // @var craft\elements\Entry $entry
     *     $entry = $e->sender;
     *
     *     // Prevent entry titles in the Parts section from getting search keywords
     *     if ($entry->section->handle === 'parts' && $e->attribute === 'title') {
     *         $e->keywords = '';
     *         $e->handled = true;
     *     }
     * });
     * ```
     *
     * @since 3.5.0
     */
    public const EVENT_DEFINE_KEYWORDS = 'defineKeywords';

    /**
     * Returns the search keywords for a given search attribute.
     */
    public function getSearchKeywords(string $attribute): string
    {
        if ($this->hasEventHandlers(self::EVENT_DEFINE_KEYWORDS)) {
            $event = new DefineAttributeKeywordsEvent(['attribute' => $attribute]);
            $this->trigger(self::EVENT_DEFINE_KEYWORDS, $event);

            if ($event->handled) {
                return $event->keywords ?? '';
            }
        }

        return $this->searchKeywords($attribute);
    }

    /**
     * Returns the search keywords for a given search attribute.
     *
     * @since 3.5.0
     */
    protected function searchKeywords(string $attribute): string
    {
        return Str::toString($this->$attribute);
    }
}
