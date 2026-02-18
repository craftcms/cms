<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Jobs\ApplyNewPropagationMethod;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Queue\BatchedJob;
use Illuminate\Support\Facades\Queue;

it('extends BatchedJob', function () {
    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
    );

    expect($job)->toBeInstanceOf(BatchedJob::class);
});

it('can be instantiated with element type', function () {
    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
    );

    expect($job)->toBeInstanceOf(ApplyNewPropagationMethod::class);
});

it('can be instantiated with criteria', function () {
    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
        criteria: ['sectionId' => 1],
    );

    expect($job)->toBeInstanceOf(ApplyNewPropagationMethod::class);
});

it('can be instantiated with null criteria', function () {
    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
    );

    expect($job)->toBeInstanceOf(ApplyNewPropagationMethod::class);
});

it('initializes duplicatedElementIds as empty array', function () {
    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
    );

    expect($job->duplicatedElementIds)->toBe([]);
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
    );

    dispatch($job);

    Queue::assertPushed(ApplyNewPropagationMethod::class);
});

it('provides a description', function () {
    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('propagation method');
});

it('can execute on entries', function () {
    Entry::factory()->create();

    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
    );

    $job->handle();

    expect($job->itemOffset)->toBeGreaterThanOrEqual(0);
});
