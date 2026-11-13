<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\Models\ActivityEvent;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ImportPlanCreated extends ImportPlanEvent
{
    protected const string LABEL = 'Created an import plan';

    protected const string ICON = 'plus';

    public static function format(ActivityEvent $event): string
    {
        return t('Created the {name} import plan', ['name' => self::planName($event)]);
    }
}
