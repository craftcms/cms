<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * RegisterDefaultCardAttributes event is triggered when registering the default card attributes for an element type.
 */
class RegisterDefaultCardAttributes
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  array  $cardAttributes  The default card attribute keys
     */
    public function __construct(
        public string $elementType,
        public array $cardAttributes = [],
    ) {}
}
