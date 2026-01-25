<?php

declare(strict_types=1);

use craft\base\Batchable;
use CraftCms\Cms\Queue\BatchedJob;
use CraftCms\Cms\Queue\JobProgressService;
use Illuminate\Support\Facades\Queue;

/**
 * Simple Batchable implementation for testing
 */
class TestBatchable implements Batchable
{
    public function __construct(
        private readonly array $items,
    ) {}

    public function count(): int
    {
        return count($this->items);
    }

    public function getSlice(int $offset, int $limit): iterable
    {
        return array_slice($this->items, $offset, $limit);
    }
}

/**
 * Concrete BatchedJob implementation for testing
 */
class TestBatchedJob extends BatchedJob
{
    public array $processedItems = [];

    public bool $beforeCalled = false;

    public bool $afterCalled = false;

    public int $beforeBatchCalls = 0;

    public int $afterBatchCalls = 0;

    public function __construct(
        public array $items = [],
    ) {}

    protected function loadData(): Batchable
    {
        return new TestBatchable($this->items);
    }

    protected function processItem(mixed $item): void
    {
        $this->processedItems[] = $item;
    }

    protected function before(): void
    {
        $this->beforeCalled = true;
    }

    protected function after(): void
    {
        $this->afterCalled = true;
    }

    protected function beforeBatch(): void
    {
        $this->beforeBatchCalls++;
    }

    protected function afterBatch(): void
    {
        $this->afterBatchCalls++;
    }
}

it('has default batch size of 100', function () {
    $job = new TestBatchedJob;

    expect($job->batchSize)->toBe(100);
});

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

it('calculates total items correctly', function () {
    $items = range(1, 10);
    $job = new TestBatchedJob($items);

    $reflection = new ReflectionMethod($job, 'totalItems');

    expect($reflection->invoke($job))->toBe(10);
});

it('calculates total batches correctly', function () {
    $items = range(1, 10);
    $job = new TestBatchedJob($items);
    $job->batchSize = 3;

    $reflection = new ReflectionMethod($job, 'totalBatches');

    expect($reflection->invoke($job))->toBe(4);
});

it('returns single batch description when only one batch', function () {
    $job = new class(['a']) extends BatchedJob
    {
        protected ?string $description = 'Test Job';

        public function __construct(
            public array $items = [],
        ) {}

        protected function loadData(): Batchable
        {
            return new TestBatchable($this->items);
        }

        protected function processItem(mixed $item): void {}
    };

    $job->batchSize = 100;

    expect($job->getDescription())->toBe('Test Job');
});

it('includes batch info in description for multi-batch jobs', function () {
    $items = range(1, 10);
    $job = new class($items) extends BatchedJob
    {
        protected ?string $description = 'Test Job';

        public function __construct(
            public array $items = [],
        ) {}

        protected function loadData(): Batchable
        {
            return new TestBatchable($this->items);
        }

        protected function processItem(mixed $item): void {}
    };

    $job->batchSize = 3;
    $job->batchIndex = 1;

    $description = $job->getDescription();

    expect($description)->toContain('batch')
        ->and($description)->toContain('2');
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

/**
 * Batched job that can simulate cancellation after processing N items
 */
class CancellableBatchedJob extends BatchedJob
{
    public array $processedItems = [];

    public int $cancelAfterItem = -1;

    public bool $jobDeleteCalled = false;

    private ?string $testUuid = null;

    public function __construct(
        public array $items = [],
    ) {}

    public function setTestUuid(string $uuid): void
    {
        $this->testUuid = $uuid;
    }

    protected function loadData(): Batchable
    {
        return new TestBatchable($this->items);
    }

    protected function processItem(mixed $item): void
    {
        $this->processedItems[] = $item;

        // Simulate cancellation by deleting the progress entry after processing N items
        if ($this->cancelAfterItem >= 0 && count($this->processedItems) >= $this->cancelAfterItem) {
            if ($this->testUuid !== null) {
                app(JobProgressService::class)->cancel($this->testUuid);
            }
        }
    }

    #[\Override]
    public function shouldStillRun(): bool
    {
        if ($this->testUuid === null) {
            return true;
        }

        return app(JobProgressService::class)->exists($this->testUuid);
    }
}

it('stops processing when cancelled between items', function () {
    $progressService = app(JobProgressService::class);
    $testUuid = 'test-cancellation-'.uniqid();

    // Create initial progress entry
    $progressService->setProgress($testUuid, 'Test job', 0);

    $items = ['a', 'b', 'c', 'd', 'e'];
    $job = new CancellableBatchedJob($items);
    $job->batchSize = 10;
    $job->setTestUuid($testUuid);
    $job->cancelAfterItem = 2; // Cancel after processing 2 items

    $job->handle();

    // Should have processed only 2 items before cancellation was detected
    // (item 1, item 2, then cancel signal set, item 3 check fails)
    expect($job->processedItems)->toBe(['a', 'b']);
});

it('does not process any items when already cancelled', function () {
    $progressService = app(JobProgressService::class);
    $testUuid = 'test-precancelled-'.uniqid();

    // Don't create a progress entry - simulates already cancelled
    $items = ['a', 'b', 'c'];
    $job = new CancellableBatchedJob($items);
    $job->batchSize = 10;
    $job->setTestUuid($testUuid);

    $job->handle();

    // Should not have processed any items
    expect($job->processedItems)->toBe([]);
});
