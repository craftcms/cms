<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

class DraftDiscarded extends ActivityEventType
{
    protected const string LABEL = 'Draft discarded';

    protected const string ICON = 'trash';
}
