<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * ElementDefaultCardAttributesResolving event is triggered when registering the default card attributes for an element type.
 */
class ElementDefaultCardAttributesResolving
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  list<string>  $cardAttributes  The default card attribute keys
     */
    public function __construct(
        public string $elementType,
        public array $cardAttributes = [],
    ) {}
}
