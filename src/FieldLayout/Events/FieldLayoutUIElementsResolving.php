<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;

/**
 * @event FieldLayoutUIElementsResolving The event that is triggered when defining UI elements for the layout.
 *
 * ```php
 * use CraftCms\Cms\FieldLayout\FieldLayout;
 * use CraftCms\Cms\FieldLayout\FieldLayout\Events\FieldLayoutUIElementsResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (FieldLayoutUIElementsResolving $event) {
 *     $event->elements[] = MyUiElement::class;
 * });
 * ```
 *
 * @see FieldLayout::getAvailableUiElements()
 */
class FieldLayoutUIElementsResolving
{
    public function __construct(
        public FieldLayout $fieldLayout,

        /**
         * @var list<FieldLayoutElement|class-string<FieldLayoutElement>|array{class: class-string<FieldLayoutElement>, ...}> The elements that should be available to the field layout designer.
         */
        public array $elements = [],
    ) {}
}
