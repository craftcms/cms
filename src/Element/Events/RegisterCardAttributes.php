<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * RegisterCardAttributes event is triggered when registering the card attributes for an element type.
 */
class RegisterCardAttributes
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  array  $cardAttributes  The card attributes
     * @param  FieldLayout|null  $fieldLayout  The field layout
     */
    public function __construct(
        public string $elementType,
        public array $cardAttributes = [],
        public ?FieldLayout $fieldLayout = null,
    ) {}
}
