<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * ElementCardAttributesResolving event is triggered when registering the card attributes for an element type.
 */
class ElementCardAttributesResolving
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  array<string, array<string, mixed>>  $cardAttributes  The card attributes
     * @param  FieldLayout|null  $fieldLayout  The field layout
     */
    public function __construct(
        public string $elementType,
        public array $cardAttributes = [],
        public ?FieldLayout $fieldLayout = null,
    ) {}
}
