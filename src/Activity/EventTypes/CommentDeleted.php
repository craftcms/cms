<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

class CommentDeleted extends CommentEvent
{
    protected const string LABEL = 'Comment removed';

    protected const string ICON = 'comment-slash';
}
