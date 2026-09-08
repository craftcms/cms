<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

class ElementDeleted extends ActivityEventType
{
    protected const string LABEL = 'Deleted';

    protected const string ICON = 'trash';
}
