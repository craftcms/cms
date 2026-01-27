<?php

declare(strict_types=1);

use CraftCms\Cms\Image\Jobs\GenerateImageTransform;
use CraftCms\Cms\Queue\Job;
use Illuminate\Support\Facades\Queue;

it('extends Job', function () {
    $job = new GenerateImageTransform(
        transformId: 1,
    );

    expect($job)->toBeInstanceOf(Job::class);
});

it('can be instantiated with transform id', function () {
    $job = new GenerateImageTransform(
        transformId: 123,
    );

    expect($job->transformId)->toBe(123);
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new GenerateImageTransform(
        transformId: 456,
    );

    dispatch($job);

    Queue::assertPushed(GenerateImageTransform::class);
});

it('provides a description', function () {
    $job = new GenerateImageTransform(
        transformId: 789,
    );

    $description = $job->getDescription();

    expect($description)->toContain('transform');
});

it('handles non-existent transform id gracefully', function () {
    $job = new GenerateImageTransform(
        transformId: 999999,
    );

    $job->handle();

    expect(true)->toBeTrue();
});

it('handles zero transform id gracefully', function () {
    $job = new GenerateImageTransform(
        transformId: 0,
    );

    $job->handle();

    expect(true)->toBeTrue();
});
