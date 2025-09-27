<?php

namespace CraftCms\Cms\Dashboard\Events;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event WidgetSaving The event that is triggered before a widget is saved.
 *
 * @since 6.0.0
 */
final class WidgetSaving
{
    use ValidatableEvent;

    public function __construct(
        public WidgetInterface $widget,
        public bool $isNew,
    ) {}
}
