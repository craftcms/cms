<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

class AfterMergeElements
{
    public function __construct(
        public int $mergedElementId,
        public int $prevailingElementId,
    ) {}
}
