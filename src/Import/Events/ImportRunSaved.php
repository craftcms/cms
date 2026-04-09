<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportRun;

/**
 * @event ImportRunSaved The event that is triggered after an import run is saved.
 */
final readonly class ImportRunSaved
{
    public function __construct(
        public ImportRun $run,
        public bool $isNew,
    ) {}
}
