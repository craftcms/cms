<?php

use CraftCms\Cms\GarbageCollection\Actions\FireRunEvent;
use CraftCms\Cms\GarbageCollection\Events\RunningGarbageCollection;
use Illuminate\Support\Facades\Event;

it('fires the event', function () {
    Event::fake();

    app(FireRunEvent::class)();

    Event::assertDispatchedOnce(RunningGarbageCollection::class);
});
