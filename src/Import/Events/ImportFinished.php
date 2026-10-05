<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @event ImportFinished The event that is triggered after an import (a queued import plan or a CLI import) has finished.
 *
 * @since 6.0.0
 */
readonly class ImportFinished
{
    /**
     * Carries the import plan (if any), the steps that were run, the run ID and whether anything failed, fired once the import has finished.
     *
     * @param  ImportPlan|null  $importPlan  The import plan that was imported, or null for CLI imports.
     * @param  BaseImporter[]  $steps  The steps (importers) that were run.
     * @param  string  $runId  The unique ID of this import run.
     * @param  bool  $hasFailures  Whether any item or job failed during the import.
     */
    public function __construct(
        public ?ImportPlan $importPlan,
        public array $steps,
        public string $runId,
        public bool $hasFailures,
    ) {}
}
