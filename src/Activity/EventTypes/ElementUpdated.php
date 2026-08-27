<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

class ElementUpdated extends ActivityEventType
{
    protected const string LABEL = 'Updated';

    protected const string ICON = 'pencil';
}
