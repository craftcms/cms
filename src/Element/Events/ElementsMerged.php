<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

/**
 * @since 6.0.0
 */
class ElementsMerged
{
    public function __construct(
        public int $mergedElementId,
        public int $prevailingElementId,
    ) {}
}
