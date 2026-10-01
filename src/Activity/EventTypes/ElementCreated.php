<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;

/**
 * @since 6.0.0
 */
class ElementCreated extends ActivityEventType
{
    protected const string LABEL = 'Created';

    protected const string ICON = 'plus';
}
