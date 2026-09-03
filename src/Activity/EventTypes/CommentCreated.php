<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

class CommentCreated extends CommentEvent
{
    protected const string LABEL = 'Commented';

    protected const string ICON = 'comment';
}
