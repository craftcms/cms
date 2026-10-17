<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @event ImportChunkStarted The event that is triggered when a queued import job starts processing a chunk of a step’s data.
 *
 * @since 6.0.0
 */
readonly class ImportChunkStarted
{
    /**
     * Carries the import plan, the step, the run ID and the chunk’s offset and limit, fired before the chunk’s first item is imported.
     *
     * @param  ImportPlan  $importPlan  The import plan being imported.
     * @param  BaseImporter  $step  The step (importer) this event concerns.
     * @param  string  $runId  The unique ID of this import run.
     * @param  int  $offset  The offset of the chunk’s first item within the step’s data.
     * @param  int  $limit  The maximum number of items this chunk will process.
     */
    public function __construct(
        public ImportPlan $importPlan,
        public BaseImporter $step,
        public string $runId,
        public int $offset,
        public int $limit,
    ) {}
}
