<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\Queue\TestClasses;

use CraftCms\Cms\Queue\BatchedJob;
use CraftCms\Cms\Queue\Contracts\Batchable;
use CraftCms\Cms\Queue\CsvBatcher;

class TestCsvBatchedJob extends BatchedJob
{
    public ?int $position = null;

    public array $processedItems = [];

    public function __construct(
        public string $path,
    ) {
        parent::__construct();
    }

    protected function loadData(): Batchable
    {
        return new CsvBatcher($this->path, position: $this->position);
    }

    protected function processItem(mixed $item): void
    {
        $this->processedItems[] = $item;
    }

    protected function afterBatch(): void
    {
        $data = $this->data();
        assert($data instanceof CsvBatcher);
        $this->position = $data->position;
    }

    #[\Override]
    public function shouldStillRun(): bool
    {
        return true;
    }
}
