<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\HasSources;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * @event RegisterFieldLayouts The event that is triggered when registering all of the field layouts
 * associated with elements from a given source.
 *
 * {@see HasSources::fieldLayouts()}
 */
final class RegisterFieldLayouts
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string|null  $source  The selected source's key, or null if all known field layouts should be returned
     * @param  FieldLayout[]  $fieldLayouts  The registered field layouts
     */
    public function __construct(
        public string $elementType,
        public ?string $source,
        public array $fieldLayouts = [],
    ) {}
}
