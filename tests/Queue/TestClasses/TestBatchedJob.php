<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Queue\TestClasses;

use CraftCms\Cms\Queue\BatchedJob;
use CraftCms\Cms\Queue\JobProgress;
use Illuminate\Contracts\Database\Query\Builder;

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

    public int $cancelAfterItem = -1;

    public bool $jobDeleteCalled = false;

    private ?string $testUuid = null;

    public function __construct(
        public array $items = [],
        public ?string $description = null,
    ) {
        parent::__construct();
    }

    public function setTestUuid(string $uuid): void
    {
        $this->testUuid = $uuid;
    }

    protected function getQuery(): Builder
    {
        return new FakeQuery()->setResult(collect($this->items));
    }

    protected function processItem(mixed $item): void
    {
        $this->processedItems[] = $item;

        // Simulate cancellation by deleting the progress entry after processing N items
        if ($this->cancelAfterItem >= 0 && count($this->processedItems) >= $this->cancelAfterItem) {
            if ($this->testUuid !== null) {
                app(JobProgress::class)->cancel($this->testUuid);
            }
        }
    }

    #[\Override]
    public function shouldStillRun(): bool
    {
        if ($this->testUuid === null) {
            return true;
        }

        return app(JobProgress::class)->exists($this->testUuid);
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
