<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @event ImportStepStarted The event that is triggered when a step of an import starts running.
 *
 * @since 6.0.0
 */
final readonly class ImportStepStarted
{
    /**
     * Carries the import plan (if any), the step and the run ID, fired before the step’s first item is imported.
     * If the job importing a step’s first chunk is retried, this event fires again.
     *
     * @param  ImportPlan|null  $importPlan  The import plan being imported, or null for CLI imports.
     * @param  BaseImporter  $step  The step (importer) this event concerns.
     * @param  string  $runId  The unique ID of this import run.
     */
    public function __construct(
        public ?ImportPlan $importPlan,
        public BaseImporter $step,
        public string $runId,
    ) {}
}
