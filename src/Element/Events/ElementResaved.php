<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Throwable;

/**
 * @since 6.0.0
 */
class ElementResaved
{
    public function __construct(
        public ElementQueryInterface $query,
        public ElementInterface $element,
        public int $position,
        public ?Throwable $exception,
    ) {}
}
