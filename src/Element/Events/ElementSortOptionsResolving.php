<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event ElementSortOptionsResolving event is triggered when registering the sort options for an element type.
 */
class ElementSortOptionsResolving
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  array<string, mixed>  $sortOptions  The sort options
     */
    public function __construct(
        public string $elementType,
        public array $sortOptions = [],
    ) {}
}
