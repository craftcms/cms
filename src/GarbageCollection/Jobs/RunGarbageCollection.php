<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Jobs;

use CraftCms\Cms\GarbageCollection\GarbageCollection;
use CraftCms\Cms\Queue\Job;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class RunGarbageCollection extends Job implements ShouldBeUnique
{
    public int $uniqueFor = 3600;

    public function handle(GarbageCollection $garbageCollection): void
    {
        $garbageCollection->run(force: true);
    }
}
