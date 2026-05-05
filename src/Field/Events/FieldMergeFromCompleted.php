<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

class FieldMergeFromCompleted extends FieldEvent
{
    public function __construct(
        FieldInterface $field,
        public FieldInterface $outgoingField,
    ) {
        parent::__construct($field);
    }
}
