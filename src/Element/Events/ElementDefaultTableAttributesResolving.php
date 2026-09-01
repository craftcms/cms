<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * ElementDefaultTableAttributesResolving event is triggered when registering the default table attributes for an element type.
 */
class ElementDefaultTableAttributesResolving
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $source  The source key
     * @param  list<string>  $tableAttributes  The default table attribute keys
     */
    public function __construct(
        public string $elementType,
        public string $source,
        public array $tableAttributes = [],
    ) {}
}
