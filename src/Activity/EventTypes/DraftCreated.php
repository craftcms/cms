<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

class DraftCreated extends ActivityEventType
{
    protected const string LABEL = 'Draft created';

    protected const string ICON = 'scribble';
}
