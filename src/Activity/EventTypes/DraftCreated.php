<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

/**
 * @since 6.0.0
 */
class DraftCreated extends ActivityEventType
{
    protected const string LABEL = 'Draft created';

    protected const string ICON = 'scribble';
}
