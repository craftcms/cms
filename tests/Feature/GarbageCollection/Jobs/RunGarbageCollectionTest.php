<?php

declare(strict_types=1);

use JMac\Testing\Double;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use CraftCms\Cms\GarbageCollection\Jobs\RunGarbageCollection;
use Illuminate\Contracts\Queue\ShouldBeUnique;

it('is unique while queued or running', function () {
    expect(new RunGarbageCollection)->toBeInstanceOf(ShouldBeUnique::class);
});

it('forces garbage collection when handled', function () {
    $garbageCollection = Double::for(GarbageCollection::class);
    $garbageCollection->shouldReceive('run')->once()->with(true);

    new RunGarbageCollection()->handle($garbageCollection);
});
