<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Events;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;

/**
 * @event WidgetSaved The event that is triggered after a widget is saved.
 */
readonly class WidgetSaved
{
    public function __construct(
        public WidgetInterface $widget,
        public bool $isNew,
    ) {}
}
