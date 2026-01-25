<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

/**
 * Job status values for queue tracking.
 *
 * These values maintain backwards compatibility with the legacy Yii2 queue system.
 */
enum JobStatus: int
{
    /**
     * Job is waiting in the queue to be processed.
     */
    case Pending = 1;

    /**
     * Job is currently being processed by a worker.
     */
    case Reserved = 2;

    /**
     * Job has completed successfully.
     */
    case Done = 3;

    /**
     * Job has failed.
     */
    case Failed = 4;

    /**
     * Job is delayed and waiting for its scheduled time.
     */
    case Delayed = 5;

    /**
     * Job was cancelled by the user.
     */
    case Cancelled = 6;
}
