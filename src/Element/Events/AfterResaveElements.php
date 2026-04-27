<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

class AfterResaveElements
{
    public function __construct(
        public ElementQueryInterface $query,
    ) {}
}
