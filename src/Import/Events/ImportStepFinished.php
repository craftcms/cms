<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @event ImportStepFinished The event that is triggered after every item of an import step has been processed.
 *
 * @since 6.0.0
 */
final readonly class ImportStepFinished
{
    /**
     * Carries the import plan (if any), the step, the run ID and whether anything in the step failed, fired once the step has finished.
     *
     * @param  ImportPlan|null  $importPlan  The import plan being imported, or null for CLI imports.
     * @param  BaseImporter  $step  The step (importer) this event concerns.
     * @param  string  $runId  The unique ID of this import run.
     * @param  bool  $hasFailures  Whether any item or job in this step failed.
     */
    public function __construct(
        public ?ImportPlan $importPlan,
        public BaseImporter $step,
        public string $runId,
        public bool $hasFailures,
    ) {}
}
