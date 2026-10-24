<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Jobs\Import as ImportJob;

/**
 * @event ImportDispatched The event that is triggered after an import plan is dispatched to the queue.
 *
 * @since 6.0.0
 */
class ImportDispatched
{
    /**
     * Carries the dispatched job steps and the import plan, fired after queue dispatch.
     *
     * @param  array<int, array{name: string, uid: string|null, job: ImportJob}>  $steps  The queue job steps to be dispatched.
     */
    public function __construct(
        public array $steps,
        public ImportPlan $importPlan,
    ) {}
}
