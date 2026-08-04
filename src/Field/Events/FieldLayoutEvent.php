<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\FieldLayout\FieldLayout;

abstract class FieldLayoutEvent
{
    public function __construct(
        public FieldLayout $layout,
    ) {}
}
