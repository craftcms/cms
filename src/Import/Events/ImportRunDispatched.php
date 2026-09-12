<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportRun;

/**
 * @event ImportRunDispatched The event that is triggered after an import run is dispatched to the queue.
 */
class ImportRunDispatched
{
    /**
     * Carries the dispatched job steps and the run, fired after queue dispatch.
     *
     * @param array $steps The queue job steps to be dispatched.
     * @param ImportRun $run
     */
    public function __construct(
        public array $steps,
        public ImportRun $run,
    ) {}
}
