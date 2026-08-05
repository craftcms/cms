<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * ElementTableAttributesResolving event is triggered when registering the table attributes for an element type.
 */
class ElementTableAttributesResolving
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  array<string, array<string, mixed>>  $tableAttributes  The table attributes
     */
    public function __construct(
        public string $elementType,
        public array $tableAttributes = [],
    ) {}
}
