<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * @since 6.0.0
 */
abstract class FieldLayoutEvent
{
    public function __construct(
        public FieldLayout $layout,
    ) {}
}
