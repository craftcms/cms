<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

class ElementTrashed extends ActivityEventType
{
    protected const string LABEL = 'Trashed';

    protected const string ICON = 'trash';
}
