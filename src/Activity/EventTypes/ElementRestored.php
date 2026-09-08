<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

class ElementRestored extends ActivityEventType
{
    protected const string LABEL = 'Restored';

    protected const string ICON = 'rotate-left';
}
