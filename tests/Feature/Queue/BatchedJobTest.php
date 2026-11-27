<?php

declare(strict_types=1);

use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Tests\Feature\Queue\TestClasses\TestBatchedJob;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Queue;

it('has default batch size of 100', function () {
    $job = new TestBatchedJob;

    expect($job->batchSize)->toBe(100);
});

it('rejects non-positive batch sizes', function (int $batchSize) {
    $job = new TestBatchedJob;

    expect(fn () => $job->batchSize = $batchSize)
        ->toThrow(InvalidArgumentException::class, 'Batch size must be at least 1.');
})->with([
    'zero' => 0,
    'negative' => -1,
]);

it('starts at batch index 0', function () {
    $job = new TestBatchedJob;

    expect($job->batchIndex)->toBe(0);
});

it('starts at item offset 0', function () {
    $job = new TestBatchedJob;

    expect($job->itemOffset)->toBe(0);
});

it('processes all items in a single batch when count is less than batch size', function () {
    $items = ['a', 'b', 'c'];
    $job = new TestBatchedJob($items);
    $job->batchSize = 10;

    $job->handle();

    expect($job->processedItems)->toBe(['a', 'b', 'c']);
});

it('calls before hook at start of first batch', function () {
    $job = new TestBatchedJob(['a', 'b']);

    $job->handle();

    expect($job->beforeCalled)->toBeTrue();
});

it('calls after hook at end of last batch', function () {
    $items = ['a', 'b', 'c'];
    $job = new TestBatchedJob($items);
    $job->batchSize = 10;

    $job->handle();

    expect($job->afterCalled)->toBeTrue();
});

it('calls beforeBatch hook for each batch', function () {
    $items = ['a', 'b', 'c'];
    $job = new TestBatchedJob($items);
    $job->batchSize = 10;

    $job->handle();

    expect($job->beforeBatchCalls)->toBe(1);
});

it('calls afterBatch hook for each batch', function () {
    $items = ['a', 'b', 'c'];
    $job = new TestBatchedJob($items);
    $job->batchSize = 10;

    $job->handle();

    expect($job->afterBatchCalls)->toBe(1);
});

it('increments itemOffset as items are processed', function () {
    $items = ['a', 'b', 'c'];
    $job = new TestBatchedJob($items);
    $job->batchSize = 10;

    $job->handle();

    expect($job->itemOffset)->toBe(3);
});

it('spawns next batch when there are more items', function () {
    Queue::fake();

    $items = range(1, 5);
    $job = new TestBatchedJob($items);
    $job->batchSize = 2;

    $job->handle();

    Queue::assertPushed(TestBatchedJob::class, fn ($pushedJob) => $pushedJob->batchIndex === 1);
});

it('does not spawn next batch when all items are processed', function () {
    Queue::fake();

    $items = ['a', 'b', 'c'];
    $job = new TestBatchedJob($items);
    $job->batchSize = 10;

    $job->handle();

    Queue::assertNotPushed(TestBatchedJob::class);
});

it('does not call before hook for subsequent batches', function () {
    $items = ['a', 'b', 'c'];
    $job = new TestBatchedJob($items);
    $job->batchSize = 10;
    $job->itemOffset = 1;

    $job->handle();

    expect($job->beforeCalled)->toBeFalse();
});

it('does not call after hook when more items remain', function () {
    Queue::fake();

    $items = range(1, 5);
    $job = new TestBatchedJob($items);
    $job->batchSize = 2;

    $job->handle();

    expect($job->afterCalled)->toBeFalse();
});

it('returns single batch description when only one batch', function () {
    $job = new TestBatchedJob(description: 'Test Job');

    $job->batchSize = 100;

    expect($job->getDescription())->toBe('Test Job');
});

it('includes batch info in description for multi-batch jobs', function () {
    $items = range(1, 10);
    $job = new TestBatchedJob($items, 'Test Job');

    $job->batchSize = 3;
    $job->batchIndex = 1;

    expect($job->getDescription())->toBe('Test Job (batch 2 of 4)');
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new TestBatchedJob(['a', 'b', 'c']);

    dispatch($job);

    Queue::assertPushed(TestBatchedJob::class);
});

it('processes empty data set without error', function () {
    $job = new TestBatchedJob([]);
    $job->batchSize = 10;

    $job->handle();

    expect($job->processedItems)->toBe([])
        ->and($job->beforeCalled)->toBeTrue()
        ->and($job->afterCalled)->toBeTrue();
});

it('clones job for next batch with incremented batchIndex', function () {
    Queue::fake();

    $items = range(1, 5);
    $job = new TestBatchedJob($items);
    $job->batchSize = 2;

    $job->handle();

    Queue::assertPushed(TestBatchedJob::class, fn ($pushedJob) => $pushedJob !== $job
        && $pushedJob->batchIndex === 1
        && $pushedJob->itemOffset === 2);
});

it('queues the next batch without the running queue job', function () {
    $job = new TestBatchedJob(range(1, 5));
    $job->batchSize = 2;
    $job->setJob(new DatabaseJob(app(), app(QueueManager::class)->connection('database'), new DatabaseJobRecord((object) [
        'id' => 1,
        'queue' => 'default',
        'attempts' => 1,
        'reserved_at' => time(),
        'available_at' => time(),
        'created_at' => time(),
        'payload' => '{}',
    ]), 'database', 'default'));

    Queue::fake()->serializeAndRestore();

    $job->handle();

    Queue::assertPushed(TestBatchedJob::class, fn (TestBatchedJob $pushedJob) => $pushedJob->job === null
        && $pushedJob->batchIndex === 1
        && $pushedJob->itemOffset === 2);
});

it('finishes when the query runs out of items before reaching its total', function () {
    Queue::fake()->serializeAndRestore();

    // The query counts 5 items but only returns 3.
    $job = new TestBatchedJob([1, 2, 3]);
    $job->count = 5;
    $job->batchSize = 2;
    $job->handle();

    $handled = [$job];

    // Run each queued batch in turn, giving up after 10 so a job that keeps spawning batches can't hang the test.
    while (count($handled) < 10 && ($next = Queue::pushed(TestBatchedJob::class)->get(count($handled) - 1)) !== null) {
        $next->handle();
        $handled[] = $next;
    }

    $last = end($handled);

    expect($handled)->toHaveCount(3)
        ->and($last->processedItems)->toBe([1, 2, 3])
        ->and($last->afterBatchCalls)->toBe(3)
        ->and($last->afterCalled)->toBeTrue();
});

it('stops processing when cancelled between items', function () {
    $progressService = app(JobProgress::class);
    $testUuid = 'test-cancellation-'.uniqid();

    // Create initial progress entry
    $progressService->setProgress($testUuid, 'Test job', 0);

    $items = ['a', 'b', 'c', 'd', 'e'];
    $job = new TestBatchedJob($items);
    $job->batchSize = 10;
    $job->setTestUuid($testUuid);
    $job->cancelAfterItem = 2; // Cancel after processing 2 items

    $job->handle();

    // Should have processed only 2 items before cancellation was detected
    // (item 1, item 2, then cancel signal set, item 3 check fails)
    expect($job->processedItems)->toBe(['a', 'b']);
});

it('does not process any items when already cancelled', function () {
    $testUuid = 'test-precancelled-'.uniqid();

    // Don't create a progress entry - simulates already cancelled
    $items = ['a', 'b', 'c'];
    $job = new TestBatchedJob($items);
    $job->batchSize = 10;
    $job->setTestUuid($testUuid);

    $job->handle();

    // Should not have processed any items
    expect($job->processedItems)->toBe([]);
});
