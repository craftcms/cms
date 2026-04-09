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

    public function __construct(
        public ImportRun $run,
        public bool $isNew,
    ) {}
}
