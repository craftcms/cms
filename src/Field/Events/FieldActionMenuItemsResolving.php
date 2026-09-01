<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

class FieldActionMenuItemsResolving extends FieldEvent
{
    public function __construct(
        FieldInterface $field,
        /** @var list<array<string, mixed>> $items */
        public array $items,
    ) {
        parent::__construct($field);
    }
}
