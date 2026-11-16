<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\Models\ActivityEvent;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ImportPlanRan extends ImportPlanEvent
{
    protected const string LABEL = 'Ran an import plan';

    protected const string ICON = 'play';

    public static function format(ActivityEvent $event): string
    {
        return t('Ran the {name} import plan', ['name' => self::planName($event)]);
    }
}
