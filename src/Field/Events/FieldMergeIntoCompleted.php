<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

class FieldMergeIntoCompleted extends FieldEvent
{
    public function __construct(
        FieldInterface $field,
        public FieldInterface $persistingField,
    ) {
        parent::__construct($field);
    }
}
