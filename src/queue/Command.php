<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\queue;

use Craft;
use yii\log\FileTarget;

/**
 * Manages application db-queue.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since  3.0
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
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Set the log target to queue.log
        $logDispatcher = Craft::$app->getLog();
        if (isset($logDispatcher->targets[0]) && $logDispatcher->targets[0] instanceof FileTarget) {
            /** @var FileTarget $logTarget */
            $logTarget = $logDispatcher->targets[0];
            $logTarget->logFile = Craft::getAlias('@storage/logs/queue.log');
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
     * Runs all jobs from db-queue.
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->queue->run();
    }

    /**
     * Listens db-queue and runs new jobs.
     * It can be used as demon process.
     *
     * @param integer $delay Number of seconds for waiting new job.
     */
    public function actionListen($delay = 3)
    {
        $this->queue->listen($delay);
    }
}
