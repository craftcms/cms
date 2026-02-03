<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

/**
 * RegisterSearchableAttributes event is triggered when registering the searchable attributes for an element type.
 *
 * @since 6.0.0
 */
final class RegisterSearchableAttributes
{
    /**
     * @param  class-string<\CraftCms\Cms\Element\ElementInterface>  $elementType  The element type class
     * @param  array  $attributes  The searchable attributes
     */
    public function __construct(
        public string $elementType,
        public array $attributes = [],
    ) {}
}
