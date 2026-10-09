<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @since 6.0.0
 */
class FieldLifecycleSaved extends FieldEvent
{
    public function __construct(
        FieldInterface $field,
        public readonly bool $isNew,
    ) {
        parent::__construct($field);
    }
}
