<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Events;

use CraftCms\Cms\GarbageCollection\GarbageCollection;

final readonly class RunningGarbageCollection
{
    public function __construct(
        public GarbageCollection $garbageCollection
    ) {}
}
