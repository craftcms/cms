<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use yii\console\ExitCode;

/**
 * Manages the queue
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 3.0
 */
class Command extends \yii\queue\cli\Command
{
    // Properties
    // =========================================================================

    /**
     * @var Queue
     */
    public $queue;

    /**
     * @var string
     */
    public $defaultAction = 'info';

    /**
     * @inheritdoc
     */
    public $verboseConfig = [
        'class' => VerboseBehavior::class,
    ];

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isWorkerAction($actionID)
    {
        return in_array($actionID, ['run', 'listen'], true);
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'info' => InfoAction::class,
        ];
    }

    /**
     * Runs all jobs in the queue
     *
     * @return int
     */
    public function actionRun(): int
    {
        $this->queue->run();
        return ExitCode::OK;
    }

    /**
     * Listens for new jobs added to the queue and runs them
     *
     * @param int $delay Number of seconds for waiting new job
     */
    public function actionListen($delay = 3)
    {
        $this->queue->listen($delay);
    }
}
