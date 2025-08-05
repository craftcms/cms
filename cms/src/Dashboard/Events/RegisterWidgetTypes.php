<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Dashboard\Events;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use Illuminate\Support\Collection;

class RegisterWidgetTypes
{
    public function __construct(
        /** @var Collection<int, class-string<WidgetInterface>> */
        public Collection $types,
    ) {}
}
