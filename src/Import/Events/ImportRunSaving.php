<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportRun;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event ImportRunSaving The event that is triggered before an import run is saved.
 */
class ImportRunSaving
{
    use ValidatableEvent;

    /**
     * Carries the run and isNew flag for a cancellable pre-save event.
     *
     * @param ImportRun $run The import run this event concerns.
     * @param bool $isNew Whether the run is newly created.
     */
    public function __construct(
        public ImportRun $run,
        public bool $isNew,
    ) {}
}
