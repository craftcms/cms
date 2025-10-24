<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Events;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;

/**
 * @event WidgetDeleted The event that is triggered after a widget is deleted.
 */
final readonly class WidgetDeleted
{
    public function __construct(
        public WidgetInterface $widget,
    ) {}
}
