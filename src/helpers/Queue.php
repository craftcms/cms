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
    public static function push(JobInterface $job, ?int $priority = null, ?int $delay = null, ?int $ttr = null): ?string
    {
        $queue = Craft::$app->getQueue();

        try {
            return $queue
                ->priority($priority)
                ->delay($delay)
                ->ttr($ttr)
                ->push($job);
        } catch (NotSupportedException $e) {
            // Some queue drivers don't support priority
            return $queue
                ->delay($delay)
                ->ttr($ttr)
                ->push($job);
        }
    }
}
