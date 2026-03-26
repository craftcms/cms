<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

class DefineFieldActionMenuItems extends FieldEvent
{
    public function __construct(
        FieldInterface $field,
        public array $items,
    ) {
        parent::__construct($field);
    }
}
