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
     * @param JobInterface $job
     * @param int|null $priority
     * @param int|null $delay
     * @param int|null $ttr
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
