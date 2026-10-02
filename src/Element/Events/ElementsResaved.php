<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

/**
 * @since 6.0.0
 */
class ElementsResaved
{
    public function __construct(
        public ElementQueryInterface $query,
    ) {}
}
