<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @event ImportChunkFinished The event that is triggered after a queued import job has processed a chunk of a step’s data.
 *
 * @since 6.0.0
 */
final readonly class ImportChunkFinished
{
    /**
     * Carries the import plan, the step, the run ID, the chunk’s offset, how many items it processed and whether any failed.
     *
     * @param  ImportPlan  $importPlan  The import plan being imported.
     * @param  BaseImporter  $step  The step (importer) this event concerns.
     * @param  string  $runId  The unique ID of this import run.
     * @param  int  $offset  The offset of the chunk’s first item within the step’s data.
     * @param  int  $count  The number of items this chunk processed.
     * @param  bool  $hasFailures  Whether any item in this chunk failed to import.
     */
    public function __construct(
        public ImportPlan $importPlan,
        public BaseImporter $step,
        public string $runId,
        public int $offset,
        public int $count,
        public bool $hasFailures,
    ) {}
}
