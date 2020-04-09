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
     * @return string|null The new job ID
     */
    public static function push(JobInterface $job, int $priority = null)
    {
        $queue = Craft::$app->getQueue();

        if ($priority !== null) {
            return $queue->push($job);
        }

        try {
            return $queue->priority($priority)->push($job);
        } catch (NotSupportedException $e) {
            // The queue probably doesn't support custom push priorities. Try again without one.
            return $queue->push($job);
        }
    }
}
