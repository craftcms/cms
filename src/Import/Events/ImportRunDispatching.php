<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportRun;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event ImportRunDispatching The event that is triggered before an import run is dispatched to the queue.
 */
class ImportRunDispatching
{
    use ValidatableEvent;

    public function __construct(
        public array $steps,
        public ImportRun $run,
    ) {}
}
