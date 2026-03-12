<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * RegisterExporters event is triggered when registering the available exporters for an element type.
 */
class RegisterExporters
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $source  The selected source's key
     * @param  array  $exporters  List of registered exporters for the element type
     */
    public function __construct(
        public string $elementType,
        public string $source,
        public array $exporters = [],
    ) {}
}
