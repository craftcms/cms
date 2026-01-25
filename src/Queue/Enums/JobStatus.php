<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Enums;

/**
 * Job status values for queue status & progress tracking.
 */
enum JobStatus: int
{
    case Pending = 1;
    case Reserved = 2;
    case Done = 3;
    case Failed = 4;
    case Delayed = 5;
    case Cancelled = 6;
}
