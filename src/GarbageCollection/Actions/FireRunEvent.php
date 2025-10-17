<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\GarbageCollection\Events\RunningGarbageCollection;
use Illuminate\Support\Facades\Event;

final class FireRunEvent extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        if (Event::hasListeners(RunningGarbageCollection::class)) {
            Event::dispatch(new RunningGarbageCollection);
        }
    }
}
