<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @since 6.0.0
 */
class FieldLifecycleSaving extends FieldEvent
{
    use ValidatableEvent;

    public function __construct(
        FieldInterface $field,
        public readonly bool $isNew,
    ) {
        parent::__construct($field);
    }
}
