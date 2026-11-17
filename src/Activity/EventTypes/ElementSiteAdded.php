<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Models\ActivityEvent;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ElementSiteAdded extends ActivityEventType
{
    protected const string LABEL = 'Added this element to a site';

    protected const string ICON = 'circle-plus';

    public static function format(ActivityEvent $event): string
    {
        return t(
            'Added this {type} to {site}',
            [
                'type' => self::subjectTypeName($event),
                'site' => $event->snapshots['site']['name'],
            ],
        );
    }
}
