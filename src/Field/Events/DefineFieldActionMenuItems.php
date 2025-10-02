<?php

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

/** @since 6.0.0 */
final class DefineFieldActionMenuItems extends FieldEvent
{
    public function __construct(
        FieldInterface $field,
        public array $items,
    ) {
        parent::__construct($field);
    }
}
