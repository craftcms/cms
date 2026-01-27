<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\queue\JobInterface;
use craft\queue\LegacyJobWrapper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\Job as LaravelJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use yii\queue\Queue as BaseQueue;

/**
 * Queue helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \Illuminate\Support\Facades\Queue} instead.
 */
class Queue
{
    /**
     * Pushes a job to the queue.
     *
     * Supports both legacy Yii2-style jobs (JobInterface) and Laravel jobs (ShouldQueue).
     * Legacy jobs are automatically wrapped in LegacyJobWrapper for Laravel compatibility.
     *
     * @param  JobInterface|ShouldQueue  $job  The job to execute via the queue.
     * @param  int|null  $priority  The job priority (ignored - Laravel doesn't support priority).
     * @param  int|null  $delay  The execution delay (in seconds).
     * @param  int|null  $ttr  The maximum time the job can run before timing out.
     * @param  BaseQueue|null  $queue  Ignored - always dispatches to Laravel queue.
     * @return string|null Always returns null - Laravel assigns UUID internally.
     */
    public static function push(
        JobInterface|ShouldQueue $job,
        ?int $priority = null,
        ?int $delay = null,
        ?int $ttr = null,
        ?BaseQueue $queue = null,
    ): ?string {
        // Wrap legacy Yii2-style job if needed
        if ($job instanceof JobInterface && !$job instanceof ShouldQueue) {
            $wrapper = new LegacyJobWrapper($job);

            if ($ttr !== null) {
                $wrapper->timeout = $ttr;
            }

            $job = $wrapper;
        } elseif ($ttr !== null && $job instanceof LaravelJob) {
            $job->timeout = $ttr;
        }

        // Dispatch with delay and lower priority if specified
        $queueName = $priority > 1024
            ? Cms::config()->lowPriorityQueueName
            : Cms::config()->queueName;

        if ($delay !== null && $delay > 0) {
            dispatch($job)->onQueue($queueName)->delay($delay);
        } else {
            dispatch($job)->onQueue($queueName);
        }

        // Laravel assigns UUID internally - we don't have access to it here
        return null;
    }
}
