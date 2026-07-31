<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportRun;

/**
 * @event ImportRunSaved The event that is triggered after an import run is saved.
 */
final readonly class ImportRunSaved
{
    /**
     * Carries the saved run and isNew flag, fired after save.
     *
     * @param ImportRun $run The import run this event concerns.
     * @param bool $isNew
     */
    public function __construct(
        public ImportRun $run,
        public bool $isNew,
    ) {}
}
