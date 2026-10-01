<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Jobs;

use CraftCms\Cms\GarbageCollection\GarbageCollection;
use CraftCms\Cms\Queue\Job;
use Illuminate\Contracts\Queue\ShouldBeUnique;

/**
 * @since 6.0.0
 */
class RunGarbageCollection extends Job implements ShouldBeUnique
{
    public int $uniqueFor = 3600;

    public function handle(GarbageCollection $garbageCollection): void
    {
        $garbageCollection->run(force: true);
    }
}
