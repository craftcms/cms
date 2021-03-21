<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use yii\base\NotSupportedException;
use yii\queue\JobInterface;

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
     * @return string|null The new job ID
     */
    public static function push(JobInterface $job, ?int $priority = null, ?int $delay = null, ?int $ttr = null)
    {
        $queue = Craft::$app->getQueue();

        if ($priority !== null) {
            try {
                $queue->priority($priority);
            } catch (NotSupportedException $e) {
            }
        }

        if ($delay !== null) {
            try {
                $queue->delay($delay);
            } catch (NotSupportedException $e) {
            }
        }

        if ($ttr !== null) {
            try {
                $queue->ttr($ttr);
            } catch (NotSupportedException $e) {
            }
        }

        return $queue->push($job);
    }
}
