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

class WidgetDeleting
{
    use ValidatableEvent;

    public function __construct(
        public WidgetInterface $widget,
    ) {}
}
