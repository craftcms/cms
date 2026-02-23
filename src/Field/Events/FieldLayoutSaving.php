<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * @event FieldLayoutSaving The event that is triggered before a field layout is saved.
 */
final class FieldLayoutSaving extends FieldLayoutEvent
{
    public function __construct(
        FieldLayout $layout,
        public bool $isNew,
    ) {
        parent::__construct($layout);
    }
}
