<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @event FieldSaved The event that is triggered after a field is saved.
 */
class FieldSaved extends FieldEvent
{
    public function __construct(
        public FieldInterface $field,
        public bool $isNew,
    ) {
        parent::__construct($field);
    }
}
