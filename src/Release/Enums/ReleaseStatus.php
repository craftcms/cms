<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Enums;

enum ReleaseStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'failed';

    public function isMutable(): bool
    {
        return in_array($this, [self::Draft, self::Failed], true);
    }
}
