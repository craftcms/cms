<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;

/**
 * @event ImportDispatched The event that is triggered after an import plan is dispatched to the queue.
 */
class ImportDispatched
{
    /**
     * Carries the dispatched job steps and the import plan, fired after queue dispatch.
     *
     * @param  array  $steps  The queue job steps to be dispatched.
     */
    public function __construct(
        public array $steps,
        public ImportPlan $importPlan,
    ) {}
}
