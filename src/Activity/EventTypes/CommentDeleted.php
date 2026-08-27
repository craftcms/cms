<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

class CommentDeleted extends CommentEvent
{
    protected const string LABEL = 'Removed comment';

    protected const string ICON = 'comment-slash';
}
