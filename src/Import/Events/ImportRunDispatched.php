<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportRun;

/**
 * @event ImportRunDispatched The event that is triggered after an import run is dispatched to the queue.
 */
class ImportRunDispatched
{
    public function __construct(
        public array $steps,
        public ImportRun $run,
    ) {}
}
