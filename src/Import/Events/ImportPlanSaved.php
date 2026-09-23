<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\ImportPlan;

/**
 * @event ImportPlanSaved The event that is triggered after an import plan is saved.
 */
final readonly class ImportPlanSaved
{
    /**
     * Carries the saved import plan and isNew flag, fired after save.
     *
     * @param  ImportPlan  $importPlan  The import plan this event concerns.
     */
    public function __construct(
        public ImportPlan $importPlan,
        public bool $isNew,
    ) {}
}
