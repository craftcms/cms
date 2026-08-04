<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @event FieldSaving The event that is triggered before a field is saved.
 */
class FieldSaving extends FieldEvent
{
    public function __construct(
        public FieldInterface $field,
        public bool $isNew,
    ) {
        parent::__construct($field);
    }
}
