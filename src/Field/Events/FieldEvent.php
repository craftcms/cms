<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @since 6.0.0
 */
abstract class FieldEvent
{
    public function __construct(
        public FieldInterface $field,
    ) {}
}
