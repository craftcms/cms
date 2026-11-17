<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

/**
 * @since 6.0.0
 */
class DraftSaved extends ActivityEventType
{
    protected const string LABEL = 'Saved a draft';

    protected const string ICON = 'floppy-disk';
}
