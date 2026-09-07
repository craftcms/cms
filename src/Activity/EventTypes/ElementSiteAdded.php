<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Models\ActivityEvent;

use function CraftCms\Cms\t;

class ElementSiteAdded extends ActivityEventType
{
    protected const string LABEL = 'Added to site';

    protected const string ICON = 'circle-plus';

    public static function format(ActivityEvent $event): string
    {
        return t(
            'Added to {site}.',
            ['site' => $event->snapshots['site']['name']],
        );
    }
}
