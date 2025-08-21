<?php

namespace CraftCms\Cms\Dashboard\Events;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;

/**
 * @event WidgetSaved The event that is triggered after a widget is saved.
 * @since 6.0.0
 */
final readonly class WidgetSaved
{
    public function __construct(
        public WidgetInterface $widget,
        public bool $isNew,
    ) {}
}
