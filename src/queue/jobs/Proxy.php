<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\queue\BaseJob;
use craft\queue\Queue;
use yii\base\InvalidConfigException;

/**
 * Proxy job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Proxy extends BaseJob
{
    /**
     * @var string The internal queue’s application component ID.
     */
    public string $queue;

    /**
     * @var string The internal job’s ID.
     */
    public string $jobId;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function execute($queue): void
    {
        $queue = Craft::$app->get($this->queue);

        if (!$queue instanceof Queue) {
            throw new InvalidConfigException(sprintf('$queue must be set to the application component ID of a %s object.', Queue::class));
        }

        $queue->executeJob($this->jobId);
    }
}
