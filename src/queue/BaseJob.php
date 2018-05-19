<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use yii\base\BaseObject;

/**
 * Job is the base class for classes representing jobs in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseJob extends BaseObject implements JobInterface
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The configured job description
     */
    public $description;

    /**
     * @var int The current progress
     */
    private $_progress;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Set the default progress
        $this->_progress = 0;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->description ?? $this->defaultDescription();
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]].
     *
     * @return string|null
     */
    protected function defaultDescription()
    {
        return null;
    }

    /**
     * Sets the job progress on the queue.
     *
     * @param \yii\queue\Queue|QueueInterface $queue
     * @param float $progress A number between 0 and 1
     */
    protected function setProgress($queue, float $progress)
    {
        if ($progress !== $this->_progress && $queue instanceof QueueInterface) {
            $queue->setProgress(round(100 * $progress));
        }
    }
}
