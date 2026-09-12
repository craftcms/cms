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

    /**
     * Carries the steps and run for a cancellable pre-dispatch event.
     *
     * @param array $steps The queue job steps to be dispatched.
     * @param ImportRun $run
     */
    public function __construct(
        public array $steps,
        public ImportRun $run,
    ) {}
}
