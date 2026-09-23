<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event ImportPlanSaving The event that is triggered before an import plan is saved.
 */
class ImportPlanSaving
{
    use ValidatableEvent;

    /**
     * Carries the import plan and isNew flag for a cancellable pre-save event.
     *
     * @param  ImportPlan  $importPlan  The import plan this event concerns.
     * @param  bool  $isNew  Whether the import plan is newly created.
     */
    public function __construct(
        public ImportPlan $importPlan,
        public bool $isNew,
    ) {}
}
