<?php

namespace CraftCms\Cms\Dashboard\Events;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event WidgetDeleting The event that is triggered before a widget is deleted.
 *
 * @since 6.0.0
 */
final class WidgetDeleting
{
    use ValidatableEvent;

    public function __construct(
        public WidgetInterface $widget,
    ) {}
}
