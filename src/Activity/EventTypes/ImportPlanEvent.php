<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Import\Data\ImportPlan;

/**
 * Something that happened to an import plan, which is the event’s subject.
 *
 * @since 6.0.0
 */
abstract class ImportPlanEvent extends ActivityEventType
{
    public function __construct(ImportPlan $importPlan)
    {
        parent::__construct(subject: new ActivitySubject(
            type: ImportPlan::class,
            id: (string) $importPlan->uid,
            label: (string) $importPlan->name,
        ));
    }

    /**
     * The import plan’s name when the event happened.
     */
    protected static function planName(ActivityEvent $event): string
    {
        return (string) ($event->snapshots['subject']['label'] ?? '');
    }
}
