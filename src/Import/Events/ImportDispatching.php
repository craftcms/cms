<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Jobs\Import as ImportJob;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event ImportDispatching The event that is triggered before an import plan is dispatched to the queue.
 *
 * @since 6.0.0
 */
class ImportDispatching
{
    use ValidatableEvent;

    /**
     * Carries the steps and import plan for a cancelable pre-dispatch event.
     *
     * Each step's job already holds the import plan, so changes made to `$importPlan` are picked up by the jobs,
     * but replacing it with a different object isn't.
     *
     * @param  array<int, array{name: string, uid: string|null, job: ImportJob}>  $steps  The queue job steps to be dispatched.
     */
    public function __construct(
        public array $steps,
        public ImportPlan $importPlan,
    ) {}
}
