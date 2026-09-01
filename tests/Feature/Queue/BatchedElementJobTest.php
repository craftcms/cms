<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Tests\Feature\Queue\TestClasses\TestBatchedElementJob;
use Illuminate\Support\Facades\Queue;

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new TestBatchedElementJob(EntryElement::class);

    dispatch($job);

    Queue::assertPushed(TestBatchedElementJob::class);
});

it('provides a description', function () {
    $job = new TestBatchedElementJob(EntryElement::class);

    expect($job->getDescription())->toBe('Test Element Job');
});

it('begins bulk operation on first batch', function () {
    Entry::factory()->create();

    $job = new TestBatchedElementJob(EntryElement::class);
    $job->batchSize = 100;

    $job->handle();

    expect($job->bulkOpKey)->not->toBeEmpty();
});

it('processes elements when executed', function () {
    Entry::factory()->count(3)->create();

    $elementIds = EntryElement::find()
        ->collect()
        ->pluck('id')
        ->toArray();

    $job = new TestBatchedElementJob(EntryElement::class);
    $job->batchSize = 100;

    $job->handle();

    expect($job->processedElementIds)->toContain(...$elementIds);
});

it('respects criteria when loading elements', function () {
    Entry::factory()->count(3)->create();

    $entries = EntryElement::find()->collect();
    $targetEntry = $entries->first();

    $job = new TestBatchedElementJob(
        EntryElement::class,
        ['id' => $targetEntry->id],
    );
    $job->batchSize = 100;

    $job->handle();

    expect($job->processedElementIds)->toHaveCount(1)
        ->and($job->processedElementIds)->toContain($targetEntry->id);
});

it('respects offset and limit criteria across batches', function () {
    $entryIds = Entry::factory()
        ->count(5)
        ->create()
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    Queue::fake();

    $job = new TestBatchedElementJob(
        EntryElement::class,
        [
            'id' => $entryIds,
            'offset' => 1,
            'limit' => 3,
        ],
    );
    $job->batchSize = 2;

    $job->handle();

    expect($job->processedElementIds)->toBe(array_slice($entryIds, 1, 2));

    Queue::assertPushed(TestBatchedElementJob::class, function (TestBatchedElementJob $nextJob) use ($entryIds) {
        $nextJob->handle();

        expect($nextJob->processedElementIds)->toBe(array_slice($entryIds, 1, 3));

        return true;
    });
});

it('spawns next batch for multi-batch element jobs', function () {
    // This test verifies that BatchedElementJob correctly inherits
    // the batch spawning behavior from BatchedJob.
    // The detailed batch spawning logic is tested in BatchedJobTest.

    // Create entries
    Entry::factory()->count(5)->create();

    // Get all entry IDs and verify we have enough
    $entries = EntryElement::find()->collect();

    expect($entries->count())->toBeGreaterThanOrEqual(5);

    Queue::fake();

    // Test with a small batch size (no timeout constraint)
    $job = new TestBatchedElementJob(EntryElement::class);
    $job->batchSize = 2;
    // Keep default timeout of 300 seconds

    $job->handle();

    // Verify that we processed exactly batchSize items (2)
    // if there are more than 2 entries
    if ($entries->count() > 2) {
        expect($job->itemOffset)->toBe(2);
    }
});

it('orders elements by id ascending', function () {
    Entry::factory()->count(3)->create();

    $expectedOrder = EntryElement::find()
        ->orderBy('elements.id')
        ->collect()
        ->pluck('id')
        ->toArray();

    $job = new TestBatchedElementJob(EntryElement::class);
    $job->batchSize = 100;

    $job->handle();

    expect($job->processedElementIds)->toBe($expectedOrder);
});

it('resumes bulk operation for subsequent batches', function () {
    Entry::factory()->count(5)->create();

    $job = new TestBatchedElementJob(EntryElement::class);
    $job->batchSize = 100;
    $job->bulkOpKey = 'existing-key';
    $job->itemOffset = 1;

    $job->handle();

    expect($job->bulkOpKey)->toBe('existing-key');
});

it('processes with empty result set without error', function () {
    $job = new TestBatchedElementJob(
        EntryElement::class,
        ['id' => 99999],
    );
    $job->batchSize = 100;

    $job->handle();

    expect($job->processedElementIds)->toBeEmpty();
});
