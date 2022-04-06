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
 * @since 3.0.0
 */
abstract class BaseJob extends BaseObject implements JobInterface
{
    /**
     * @var string|null The configured job description.
     *
     * ::: tip
     * Run the description through [[\craft\i18n\Translation::prep()]] rather than [[\yii\BaseYii::t()|Craft::t()]]
     * so it can be lazy-translated for users’ preferred languages rather that the current app language.
     * :::
     */
    public ?string $description = null;

    /**
     * @var int|float The current progress
     */
    private int|float $_progress;

    /**
     * @var string|null The current progress label
     */
    private ?string $_progressLabel = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Set the default progress
        $this->_progress = 0;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->description ?? $this->defaultDescription();
    }

    /**
     * Returns a default description for [[getDescription()]].
     *
     * ::: tip
     * Run the description through [[\craft\i18n\Translation::prep()]] rather than [[\yii\BaseYii::t()|Craft::t()]]
     * so it can be lazy-translated for users’ preferred languages rather that the current app language.
     * :::
     *
     * @return string|null
     */
    protected function defaultDescription(): ?string
    {
        return null;
    }

    /**
     * Sets the job progress on the queue.
     *
     * ::: tip
     * Run the label through [[\craft\i18n\Translation::prep()]] rather than [[\yii\BaseYii::t()|Craft::t()]]
     * so it can be lazy-translated for users’ preferred languages rather that the current app language.
     * :::
     *
     * @param \yii\queue\Queue|QueueInterface $queue
     * @param float $progress A number between 0 and 1
     * @param string|null $label The progress label
     */
    protected function setProgress(\yii\queue\Queue|QueueInterface $queue, float $progress, ?string $label = null): void
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
                $queue->setProgress((int)$progress, $label);
            }
        }
    }
}
