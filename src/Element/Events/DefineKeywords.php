<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * DefineKeywords event is triggered when defining the search keywords for an element attribute.
 *
 * If `handled` is set to `true`, the custom `keywords` value will be used instead of the default.
 */
class DefineKeywords
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  string  $attribute  The element attribute
     * @param  string  $keywords  Custom keywords (only used if `handled` is true)
     * @param  bool  $handled  Whether the event has been handled
     */
    public function __construct(
        public ElementInterface $element,
        public string $attribute,
        public string $keywords = '',
        public bool $handled = false,
    ) {}
}
