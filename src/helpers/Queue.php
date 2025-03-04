<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\queue\BaseBatchedJob;
use yii\base\NotSupportedException;
use yii\queue\JobInterface;
use yii\queue\Queue as BaseQueue;

/**
 * Queue helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Queue
{
    /**
     * Pushes a job to the main app queue.
     *
     * @param JobInterface $job The job to execute via the queue.
     * @param int|null $priority The job priority, if supported. Jobs with a
     * lower priority will be executed first. (Default is 1024.)
     * @param int|null $delay The execution delay (in seconds), if supported.
     * @param int|null $ttr The maximum time the queue should wait around for
     * the job to be handled before assuming it failed.
     * @param BaseQueue|null $queue The queue to push to
     * @return string|null The new job ID
     */
    public static function push(
        JobInterface $job,
        ?int $priority = null,
        ?int $delay = null,
        ?int $ttr = null,
        ?BaseQueue $queue = null,
    ): ?string {
        if ($queue === null) {
            $queue = Craft::$app->getQueue();
        }

        if ($job instanceof BaseBatchedJob) {
            // Keep track of the priority and TTR in case there will be additional jobs
            $job->priority = $priority;
            $job->ttr = $ttr;
        }

        try {
            return $queue
                ->priority($priority)
                ->delay($delay)
                ->ttr($ttr)
                ->push($job);
        } catch (NotSupportedException) {
            // Some queue drivers don't support priority
            return $queue
                ->delay($delay)
                ->ttr($ttr)
                ->push($job);
        }
    }
}
