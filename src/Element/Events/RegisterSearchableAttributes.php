<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event RegisterSearchableAttributes event is triggered when registering the searchable attributes for an element type.
 */
final class RegisterSearchableAttributes
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  array  $attributes  The searchable attributes
     */
    public function __construct(
        public string $elementType,
        public array $attributes = [],
    ) {}
}
