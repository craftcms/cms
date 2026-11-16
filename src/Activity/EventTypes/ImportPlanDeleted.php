<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\Models\ActivityEvent;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ImportPlanDeleted extends ImportPlanEvent
{
    protected const string LABEL = 'Deleted an import plan';

    protected const string ICON = 'trash';

    public static function format(ActivityEvent $event): string
    {
        return t('Deleted the {name} import plan', ['name' => self::planName($event)]);
    }
}
