<?php
/**
 * Created by PhpStorm.
 * User: os
 * Date: 26.01.18
 * Time: 12:33
 */

namespace craft\queue;

use craft\log\FileTarget;
use yii\queue\ErrorEvent;
use yii\queue\ExecEvent;

class QueueLogBehaviour extends VerboseBehavior
{
    /**
     * @var float timestamp
     */
    private $jobStartedAt;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_EXEC  => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Set the log target to queue.log
        $logDispatcher = \Craft::$app->getLog();
        foreach ($logDispatcher->targets as $target) {
            if ($target instanceof FileTarget) {
                $target->logFile = \Craft::getAlias('@storage/logs/queue.log');
                $target->logVars = [];
            }
        }

        // Prevent verbose query logs
        if (!\Craft::$app->getConfig()->getGeneral()->devMode) {
            $DbConnection = \Craft::$app->getDb();
            $DbConnection->enableLogging   = false;
            $DbConnection->enableProfiling = false;
        }
    }


    /**
     * @param ExecEvent $event
     */
    public function beforeExec(ExecEvent $event)
    {
        $this->jobStartedAt = microtime(true);

        \Craft::info(sprintf(
            "%s - Started",
            parent::jobTitle($event)
        ));
    }

    /**
     * @param ExecEvent $event
     */
    public function afterExec(ExecEvent $event)
    {
        $duration = $this->getDuration();

        \Craft::info(sprintf(
            "%s - Done (%s s)",
            parent::jobTitle($event),
            $duration
        ));
    }

    /**
     * @param ErrorEvent $event
     */
    public function afterError(ErrorEvent $event)
    {
        $duration = $this->getDuration();
        $error    = $event->error->getMessage();

        \Craft::error(sprintf(
            "%s - Error (%s s): %s",
            parent::jobTitle($event),
            $duration,
            $error
        ));

    }


    /**
     * @return string
     */
    protected function getDuration(): string
    {
        return number_format(round(microtime(true) - $this->jobStartedAt, 3), 3);
    }


}
