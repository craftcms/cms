<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Models\ActivityEvent;

use function CraftCms\Cms\t;

class ElementSiteRemoved extends ActivityEventType
{
    protected const string LABEL = 'Removed from site';

    protected const string ICON = 'circle-minus';

    public static function format(ActivityEvent $event): string
    {
        return t(
            'Removed from {site}.',
            ['site' => $event->snapshots['site']['name']],
        );
    }
}
