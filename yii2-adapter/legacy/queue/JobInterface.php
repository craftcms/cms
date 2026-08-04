<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use yii\queue\Queue;

/**
 * JobInterface defines the common interface to be implemented by job classes.
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[JobTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0
 */
interface JobInterface extends \yii\queue\JobInterface
{
    /**
     * Returns the description that should be used for the job.
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * @param Queue|QueueInterface $queue The queue the job belongs to
     */
    public function execute($queue): void;
}
