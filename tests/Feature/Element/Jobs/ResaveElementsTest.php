<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Jobs\ResaveElements;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Queue\JobProgress;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;

beforeEach(function () {
    $this->progressService = app(JobProgress::class);
});

it('can be instantiated with element type', function () {
    $job = new ResaveElements(
        elementType: EntryElement::class,
    );

    expect($job)->toBeInstanceOf(ResaveElements::class);
});

it('can be instantiated with criteria', function () {
    $job = new ResaveElements(
        elementType: EntryElement::class,
        criteria: ['sectionId' => 1],
    );

    expect($job)->toBeInstanceOf(ResaveElements::class);
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new ResaveElements(
        elementType: EntryElement::class,
    );

    dispatch($job);

    Queue::assertPushed(ResaveElements::class);
});

it('provides a description', function () {
    $job = new ResaveElements(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('entries');
});

it('resaves elements when executed', function () {
    Entry::factory()->count(3)->create();

    $originalDates = EntryElement::find()
        ->collect()
        ->pluck('dateUpdated', 'id')
        ->toArray();

    // Wait a second to ensure timestamp difference
    Sleep::sleep(1);

    $job = new ResaveElements(
        elementType: EntryElement::class,
        touch: true,
    );

    // Execute the job directly (not via queue for testing)
    $job->handle();

    $newDates = EntryElement::find()
        ->collect()
        ->pluck('dateUpdated', 'id')
        ->toArray();

    foreach ($originalDates as $id => $originalDate) {
        expect($newDates[$id])->not->toBe($originalDate);
    }
});

it('respects criteria when resaving', function () {
    Entry::factory()->count(3)->create();

    $entries = EntryElement::find()->collect();
    $firstEntry = $entries->first();

    Sleep::sleep(1);

    $job = new ResaveElements(
        elementType: EntryElement::class,
        criteria: ['id' => $firstEntry->id],
        touch: true,
    );

    $job->handle();

    $updatedFirst = EntryElement::findOne($firstEntry->id);
    $otherEntry = EntryElement::findOne($entries->skip(1)->first()->id);

    // First entry should have been touched
    expect($updatedFirst->dateUpdated)->not->toBe($firstEntry->dateUpdated);
});

it('can set an attribute value', function () {
    Entry::factory()->create();

    $entry = EntryElement::findOne();
    $originalTitle = $entry->title;

    $job = new ResaveElements(
        elementType: EntryElement::class,
        set: 'title',
        to: 'fn() => "Updated Title"',
    );

    $job->handle();

    $updatedEntry = EntryElement::findOne($entry->id);

    expect($updatedEntry->title)->toBe('Updated Title');
});

it('respects ifEmpty when setting attribute', function () {
    Entry::factory()->create();

    $entry = EntryElement::findOne();
    $originalTitle = $entry->title;

    $job = new ResaveElements(
        elementType: EntryElement::class,
        set: 'title',
        to: 'fn() => "New Title"',
        ifEmpty: true,
    );

    $job->handle();

    $updatedEntry = EntryElement::findOne($entry->id);

    // Title should remain unchanged since it wasn't empty
    expect($updatedEntry->title)->toBe($originalTitle);
});

it('spawns next batch when there are more items', function () {
    Entry::factory()->count(3)->create();
    Queue::fake();

    $job = new ResaveElements(
        elementType: EntryElement::class,
        set: 'title',
        to: '=Updated {{ object.id }}',
        batchSize: 2,
    );
    $job->handle();

    expect($job->itemOffset)->toBe(2);
    $next = unserialize(serialize(Queue::pushed(ResaveElements::class)->sole()));
    expect($next->batchIndex)->toBe(1);
    $next->handle();

    foreach (EntryElement::find()->all() as $entry) {
        expect($entry->title)->toBe("Updated $entry->id");
    }
});

it('includes batch info in description for multi-batch jobs', function () {
    Entry::factory()->count(10)->create();

    $job = new ResaveElements(
        elementType: EntryElement::class,
    );
    $job->batchSize = 3;
    $job->batchIndex = 1;

    $description = $job->getDescription();

    expect($description)->toContain('batch');
});
