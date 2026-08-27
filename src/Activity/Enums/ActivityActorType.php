<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Enums;

enum ActivityActorType: string
{
    case User = 'user';
    case System = 'system';
    case Anonymous = 'anonymous';
}
