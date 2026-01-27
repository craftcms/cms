<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\GarbageCollection\Events\RunningGarbageCollection;

final class FireRunEvent extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        event(new RunningGarbageCollection($this->garbageCollection));
    }
}
