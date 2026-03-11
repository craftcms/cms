<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;

/**
 * @event DefineUIElements The event that is triggered when defining UI elements for the layout.
 *
 * ```php
 * use CraftCms\Cms\FieldLayout\FieldLayout;
 * use CraftCms\Cms\FieldLayout\FieldLayout\Events\DefineUIElements;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (DefineUIElements $event) {
 *     $event->elements[] = MyUiElement::class;
 * });
 * ```
 *
 * @see FieldLayout::getAvailableUiElements()
 */
final class DefineUIElements
{
    public function __construct(
        public FieldLayout $fieldLayout,

        /**
         * @var FieldLayoutElement[]|string[]|array[] The elements that should be available to the field layout designer.
         */
        public array $elements = [],
    ) {}
}
