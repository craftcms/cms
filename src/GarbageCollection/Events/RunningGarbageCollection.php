<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Events;

use CraftCms\Cms\GarbageCollection\GarbageCollection;

readonly class RunningGarbageCollection
{
    public function __construct(
        public GarbageCollection $garbageCollection
    ) {}
}
