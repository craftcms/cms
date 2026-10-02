<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @since 6.0.0
 */
class FieldMergeIntoCompleted extends FieldEvent
{
    public function __construct(
        FieldInterface $field,
        public FieldInterface $persistingField,
    ) {
        parent::__construct($field);
    }
}
