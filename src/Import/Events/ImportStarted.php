<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @event ImportStarted The event that is triggered when an import (a queued import plan or a CLI import) starts running.
 *
 * @since 6.0.0
 */
final readonly class ImportStarted
{
    /**
     * Carries the import plan (if any), the steps that will be run and the run ID, fired before any step starts.
     *
     * @param  ImportPlan|null  $importPlan  The import plan being imported, or null for CLI imports.
     * @param  BaseImporter[]  $steps  The steps (importers) that will be run.
     * @param  string  $runId  The unique ID of this import run.
     */
    public function __construct(
        public ?ImportPlan $importPlan,
        public array $steps,
        public string $runId,
    ) {}
}
