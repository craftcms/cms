<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

class DraftSaved extends ActivityEventType
{
    protected const string LABEL = 'Draft saved';

    protected const string ICON = 'floppy-disk';
}
