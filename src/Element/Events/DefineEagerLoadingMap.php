<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * DefineEagerLoadingMap event is triggered when defining an eager-loading map.
 *
 * Set `elementType` and `map` to define a custom eager-loading map for the handle.
 *
 * @since 6.0.0
 */
final class DefineEagerLoadingMap
{
    /**
     * @param  class-string<\CraftCms\Cms\Element\ElementInterface>  $elementType  The element type class being queried
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @param  string  $handle  The property handle used to identify which target elements should be included in the map
     * @param  class-string<ElementInterface>|null  $targetElementType  The element type class to eager-load
     * @param  array|null  $map  An array of element ID mappings, where each element is a sub-array with `source` and `target` keys
     * @param  array|null  $criteria  Any criteria parameters that should be applied to the element query when fetching the eager-loaded elements
     */
    public function __construct(
        public string $elementType,
        public array $sourceElements,
        public string $handle,
        public ?string $targetElementType = null,
        public ?array $map = null,
        public ?array $criteria = null,
    ) {}
}
