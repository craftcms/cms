<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

/**
 * Manages application db-queue.
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
     *
     */
    protected function isWorkerAction($actionID)
    {
        return in_array($actionID, ['run', 'listen'], true);
    }

    // Public Methods
    // =========================================================================

    /**
     *
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }

    /**
     *
     */
    public function actions()
    {
        return [
            'info' => InfoAction::class,
        ];
    }

    /**
     * Runs all jobs from db-queue.
     *
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->queue->run();
    }

    /**
     * Listens db-queue and runs new jobs.
     *
     * It can be used as demon process.
     *
     * @param integer $delay Number of seconds for waiting new job.
     */
    public function actionListen($delay = 3)
    {
        $this->queue->listen($delay);
    }
}
