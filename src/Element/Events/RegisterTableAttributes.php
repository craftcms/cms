<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * RegisterTableAttributes event is triggered when registering the table attributes for an element type.
 */
class RegisterTableAttributes
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  array  $tableAttributes  The table attributes
     */
    public function __construct(
        public string $elementType,
        public array $tableAttributes = [],
    ) {}
}
