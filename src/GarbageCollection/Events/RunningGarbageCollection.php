<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Events;

use CraftCms\Cms\GarbageCollection\GarbageCollection;

/**
 * @since 6.0.0
 */
readonly class RunningGarbageCollection
{
    public function __construct(
        public GarbageCollection $garbageCollection
    ) {}
}
