<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

class CommentEdited extends CommentEvent
{
    protected const string LABEL = 'Comment edited';

    protected const string ICON = 'comment';
}
