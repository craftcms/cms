<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event RegisterSortOptions event is triggered when registering the sort options for an element type.
 */
class RegisterSortOptions
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  array  $sortOptions  The sort options
     */
    public function __construct(
        public string $elementType,
        public array $sortOptions = [],
    ) {}
}
