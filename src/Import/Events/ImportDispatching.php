<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Jobs\Import as ImportJob;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event ImportDispatching The event that is triggered before an import plan is dispatched to the queue.
 */
class ImportDispatching
{
    use ValidatableEvent;

    /**
     * Carries the steps and import plan for a cancellable pre-dispatch event.
     *
     * @param  array<int, array{name: string, uid: string|null, job: ImportJob}>  $steps  The queue job steps to be dispatched.
     */
    public function __construct(
        public array $steps,
        public ImportPlan $importPlan,
    ) {}
}
