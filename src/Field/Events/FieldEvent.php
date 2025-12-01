<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

class FieldEvent
{
    public function __construct(
        public FieldInterface $field,
    ) {}
}
