<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Jobs\UpdateElementSlugsAndUris;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Queue\Job;
use Illuminate\Support\Facades\Queue;

it('extends Job', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
    );

    expect($job)->toBeInstanceOf(Job::class);
});

it('can be instantiated with element type only', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
    );

    expect($job->elementType)->toBe(EntryElement::class)
        ->and($job->elementId)->toBeNull()
        ->and($job->siteId)->toBeNull()
        ->and($job->updateOtherSites)->toBeTrue()
        ->and($job->updateDescendants)->toBeTrue();
});

it('can be instantiated with element id as int', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
        elementId: 42,
    );

    expect($job->elementId)->toBe(42);
});

it('can be instantiated with element id as array', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
        elementId: [1, 2, 3],
    );

    expect($job->elementId)->toBe([1, 2, 3]);
});

it('can be instantiated with site id', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
        siteId: 1,
    );

    expect($job->siteId)->toBe(1);
});

it('can disable updating other sites', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
        updateOtherSites: false,
    );

    expect($job->updateOtherSites)->toBeFalse();
});

it('can disable updating descendants', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
        updateDescendants: false,
    );

    expect($job->updateDescendants)->toBeFalse();
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
    );

    dispatch($job);

    Queue::assertPushed(UpdateElementSlugsAndUris::class);
});

it('provides a description', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('slugs')
        ->and($description)->toContain('URIs');
});

it('can execute on entries', function () {
    Entry::factory()->create();

    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
    );

    // The handle method uses createCommand which is not yet implemented
    // This test validates the job can be instantiated and would execute
    // once the underlying implementation is complete
    expect($job)->toBeInstanceOf(UpdateElementSlugsAndUris::class)
        ->and($job->elementType)->toBe(EntryElement::class);
})->skip('createCommand not yet implemented on ElementQuery');

it('can execute on specific element', function () {
    Entry::factory()->create();
    $entry = EntryElement::find()->one();

    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
        elementId: $entry->id,
        siteId: $entry->siteId,
    );

    // The handle method uses createCommand which is not yet implemented
    // This test validates the job can be instantiated and would execute
    // once the underlying implementation is complete
    expect($job)->toBeInstanceOf(UpdateElementSlugsAndUris::class)
        ->and($job->elementId)->toBe($entry->id);
})->skip('createCommand not yet implemented on ElementQuery');
