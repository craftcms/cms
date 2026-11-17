<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Actions\ClientAction;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * ElementActionsResolving event is triggered when registering the available bulk actions for an element type.
 *
 * @since 6.0.0
 */
class ElementActionsResolving
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $source  The selected source's key
     * @param  list<mixed>  $actions  List of registered bulk actions for the element type: element action classes, configs, or instances, and {@see ClientAction}s
     */
    public function __construct(
        public string $elementType,
        public string $source,
        public array $actions = [],
    ) {}
}
