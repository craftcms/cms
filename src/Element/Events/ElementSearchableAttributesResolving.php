<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event ElementSearchableAttributesResolving event is triggered when registering the searchable attributes for an element type.
 */
class ElementSearchableAttributesResolving
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  list<string>  $attributes  The searchable attributes
     */
    public function __construct(
        public string $elementType,
        public array $attributes = [],
    ) {}
}
