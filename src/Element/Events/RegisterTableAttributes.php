<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

/**
 * RegisterTableAttributes event is triggered when registering the table attributes for an element type.
 *
 * @since 6.0.0
 */
final class RegisterTableAttributes
{
    /**
     * @param  class-string<\CraftCms\Cms\Element\ElementInterface>  $elementType  The element type class
     * @param  array  $tableAttributes  The table attributes
     */
    public function __construct(
        public string $elementType,
        public array $tableAttributes = [],
    ) {}
}
