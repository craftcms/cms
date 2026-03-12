<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

class DefineElementCriteria
{
    public function __construct(
        /** @var array The criteria that should be used to query for elements. */
        public array $criteria = [],
    ) {}
}
