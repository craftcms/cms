<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

/**
 * RegisterSortOptions event is triggered when registering the sort options for an element type.
 *
 * @since 6.0.0
 */
final class RegisterSortOptions
{
    /**
     * @param  class-string<\CraftCms\Cms\Element\ElementInterface>  $elementType  The element type class
     * @param  array  $sortOptions  The sort options
     */
    public function __construct(
        public string $elementType,
        public array $sortOptions = [],
    ) {}
}
