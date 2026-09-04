<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

class DraftApplied extends ActivityEventType
{
    protected const string LABEL = 'Draft applied';

    protected const string ICON = 'check';
}
