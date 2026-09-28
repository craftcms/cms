<?php

declare(strict_types=1);

use CraftCms\Cms\Image\Jobs\GenerateImageTransform;

it('uses the transform id as its unique id', function () {
    $job = new GenerateImageTransform(transformId: 123);

    expect($job->uniqueId())->toBe('123');
});

it('provides a description', function () {
    $job = new GenerateImageTransform(
        transformId: 789,
    );

    $description = $job->getDescription();

    expect($description)->toContain('transform');
});
