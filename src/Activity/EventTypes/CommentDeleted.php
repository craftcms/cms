<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\Models\ActivityEvent;

use function CraftCms\Cms\t;

class CommentDeleted extends CommentEvent
{
    protected const string LABEL = 'Comment removed';

    protected const string ICON = 'comment-slash';

    public static function format(ActivityEvent $event): string
    {
        return t('Removed a comment.');
    }
}
