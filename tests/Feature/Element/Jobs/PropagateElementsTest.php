<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Jobs\PropagateElements;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Queue\BatchedElementJob;
use Illuminate\Support\Facades\Queue;

it('extends BatchedElementJob', function () {
    $job = new PropagateElements(
        elementType: EntryElement::class,
    );

    expect($job)->toBeInstanceOf(BatchedElementJob::class);
});

it('can be instantiated with element type', function () {
    $job = new PropagateElements(
        elementType: EntryElement::class,
    );

    expect($job)->toBeInstanceOf(PropagateElements::class);
});

it('can be instantiated with criteria', function () {
    $job = new PropagateElements(
        elementType: EntryElement::class,
        criteria: ['sectionId' => 1],
    );

    expect($job)->toBeInstanceOf(PropagateElements::class);
});

it('can be instantiated with site id as int', function () {
    $job = new PropagateElements(
        elementType: EntryElement::class,
        siteId: 1,
    );

    expect($job->siteId)->toBe([1]);
});

it('can be instantiated with site id as array', function () {
    $job = new PropagateElements(
        elementType: EntryElement::class,
        siteId: [1, 2, 3],
    );

    expect($job->siteId)->toBe([1, 2, 3]);
});

it('can be instantiated with null site id', function () {
    $job = new PropagateElements(
        elementType: EntryElement::class,
    );

    expect($job->siteId)->toBeNull();
});

it('can be instantiated with isNewSite flag', function () {
    $job = new PropagateElements(
        elementType: EntryElement::class,
        isNewSite: true,
    );

    expect($job->isNewSite)->toBeTrue();
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new PropagateElements(
        elementType: EntryElement::class,
    );

    dispatch($job);

    Queue::assertPushed(PropagateElements::class);
});

it('provides a description', function () {
    Entry::factory()->create();

    $job = new PropagateElements(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('Propagating');
});

it('uses singular description when only one element', function () {
    Entry::factory()->create();

    $job = new PropagateElements(
        elementType: EntryElement::class,
        criteria: ['id' => EntryElement::find()->one()->id],
    );

    $description = $job->getDescription();

    expect($description)->toContain('entry');
});

it('uses plural description when multiple elements', function () {
    Entry::factory()->count(3)->create();

    $job = new PropagateElements(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('entries');
});
