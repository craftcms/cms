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

    /**
     * @var string|null The current progress label
     */
    private $_progressLabel;

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
     * @param string|null $label The progress label
     */
    protected function setProgress($queue, float $progress, string $label = null)
    {
        $progress = round(100 * $progress);

        if (
            $progress !== $this->_progress ||
            ($label !== null && $label !== $this->_progressLabel)
        ) {
            $this->_progress = $progress;

            // If $label == null, leave the existing value alone
            if ($label !== null) {
                $this->_progressLabel = $label;
            }

            if ($queue instanceof QueueInterface) {
                $queue->setProgress($progress, $label);
            }
        }
    }
}
