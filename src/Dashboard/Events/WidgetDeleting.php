<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Events;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event WidgetDeleting The event that is triggered before a widget is deleted.
 */
final class WidgetDeleting
{
    use ValidatableEvent;

    public function __construct(
        public WidgetInterface $widget,
    ) {}
}
