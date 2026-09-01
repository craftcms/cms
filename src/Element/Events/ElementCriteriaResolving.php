<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;

class ElementCriteriaResolving
{
    public function __construct(
        /** @var array<string, mixed> The criteria that should be used to query for elements. */
        public array $criteria = [],

        public ?FieldInterface $field = null,
    ) {}
}
