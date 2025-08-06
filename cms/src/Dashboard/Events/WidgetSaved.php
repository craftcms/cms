<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Dashboard\Events;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Support\Concerns\ValidatableEvent;

/**
 * @event WidgetSaved The event that is triggered after a widget is saved.
 */
class WidgetSaved
{
    use ValidatableEvent;

    public function __construct(
        public WidgetInterface $widget,
        public bool $isNew,
    ) {}
}
