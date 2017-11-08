<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\queue;

use craft\base\SavableComponentInterface;
use yii\queue\Job;

/**
 * JobInterface defines the common interface to be implemented by job classes.
 *
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[JobTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface JobInterface extends Job
{
    /**
     * Returns the description that should be used for the job.
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * @param \yii\queue\Queue|QueueInterface $queue The queue the job belongs to
     */
    public function execute($queue);
}
