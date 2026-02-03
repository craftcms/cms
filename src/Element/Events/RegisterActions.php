<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

/**
 * RegisterActions event is triggered when registering the available bulk actions for an element type.
 *
 * @since 6.0.0
 */
final class RegisterActions
{
    /**
     * @param  class-string<\CraftCms\Cms\Element\ElementInterface>  $elementType  The element type class
     * @param  string  $source  The selected source's key
     * @param  array  $actions  List of registered bulk actions for the element type
     */
    public function __construct(
        public string $elementType,
        public string $source,
        public array $actions = [],
    ) {}
}
