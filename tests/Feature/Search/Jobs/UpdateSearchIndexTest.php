<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Search\Jobs\UpdateSearchIndex;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('extends Job', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
    );

    expect($job)->toBeInstanceOf(Job::class);
});

it('can be instantiated with element type only', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
    );

    expect($job->elementType)->toBe(EntryElement::class)
        ->and($job->elementId)->toBeNull()
        ->and($job->siteId)->toBe('*')
        ->and($job->fieldHandles)->toBeNull()
        ->and($job->queued)->toBeFalse();
});

it('can be instantiated with element id as int', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
        elementId: 42,
    );

    expect($job->elementId)->toBe(42);
});

it('can be instantiated with element id as array', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
        elementId: [1, 2, 3],
    );

    expect($job->elementId)->toBe([1, 2, 3]);
});

it('can be instantiated with specific site id', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
        siteId: 1,
    );

    expect($job->siteId)->toBe(1);
});

it('can be instantiated with field handles', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
        fieldHandles: ['title', 'body'],
    );

    expect($job->fieldHandles)->toBe(['title', 'body']);
});

it('can be instantiated with queued flag', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
        elementId: 1,
        siteId: 1,
        queued: true,
    );

    expect($job->queued)->toBeTrue();
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
    );

    dispatch($job);

    Queue::assertPushed(UpdateSearchIndex::class);
});

it('provides a description', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('search');
});

it('can execute on entries', function () {
    Entry::factory()->create();

    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
    );

    $job->handle();

    expect(true)->toBeTrue();
});

it('can execute queued updates on the sync queue', function () {
    $entry = Entry::factory()->create();
    $siteId = Sites::getCurrentSite()->id;

    DB::table(Table::SEARCHINDEX)
        ->where('elementId', $entry->id)
        ->delete();

    $jobId = DB::table(Table::SEARCHINDEXQUEUE)->insertGetId([
        'elementId' => $entry->id,
        'siteId' => $siteId,
        'reserved' => false,
    ]);

    dispatch_sync(new UpdateSearchIndex(
        elementType: EntryElement::class,
        elementId: $entry->id,
        siteId: $siteId,
        queued: true,
    ));

    expect(DB::table(Table::SEARCHINDEX)
        ->where('elementId', $entry->id)
        ->exists())->toBeTrue()
        ->and(DB::table(Table::SEARCHINDEXQUEUE)
            ->where('id', $jobId)
            ->exists())->toBeFalse();
});

it('handles case with no matching elements', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
        elementId: 99999,
    );

    $job->handle();

    expect(true)->toBeTrue();
});

it('throws exception when queued with non-int element id', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
        elementId: [1, 2, 3],
        siteId: 1,
        queued: true,
    );

    expect(fn () => $job->handle())->toThrow(InvalidArgumentException::class);
});

it('throws exception when queued with non-int site id', function () {
    $job = new UpdateSearchIndex(
        elementType: EntryElement::class,
        elementId: 1,
        siteId: '*',
        queued: true,
    );

    expect(fn () => $job->handle())->toThrow(InvalidArgumentException::class);
});
