<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use Craft;
use craft\helpers\App;
use craft\log\Dispatcher;
use craft\log\FileTarget;
use craft\log\MonologTarget;
use Illuminate\Support\Collection;
use Monolog\Handler\RotatingFileHandler;
use samdark\log\PsrTarget;
use yii\log\Target;
use yii\queue\ExecEvent;

/**
 * Queue Log Behavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class QueueLogBehavior extends VerboseBehavior
{
    /**
     * @var float timestamp
     */
    private float $_jobStartedAt;

    /**
     * @var bool Whether any jobs have executed yet
     */
    private bool $_jobExecuted = false;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_EXEC => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }

    /**
     * @param ExecEvent $event
     */
    public function beforeExec(ExecEvent $event): void
    {
        if (!$this->_jobExecuted) {
            $this->_enableLogTarget();
        }

        $this->_jobStartedAt = microtime(true);
        Craft::info(sprintf('%s - Started', parent::jobTitle($event)), __METHOD__);
    }

    /**
     * @inheritdoc
     */
    public function afterExec(ExecEvent $event): void
    {
        $duration = $this->_formattedDuration();
        Craft::info(sprintf('%s - Done (time: %s)', parent::jobTitle($event), $duration), __METHOD__);
    }

    /**
     * @inheritdoc
     */
    public function afterError(ExecEvent $event): void
    {
        $duration = $this->_formattedDuration();

        if (!$event->error) {
            Craft::error(sprintf('%s - Error (time: %s)', parent::jobTitle($event), $duration), __METHOD__);
            return;
        }

        $error = $event->error->getMessage();
        Craft::error(sprintf('%s - Error (time: %s): %s', parent::jobTitle($event), $duration, $error), __METHOD__);
        Craft::$app->getErrorHandler()->logException($event->error);
    }

    /**
     * Enables the log target logs will get flushed to.
     */
    private function _enableLogTarget(): void
    {
        Collection::make(Craft::$app->getLog()->targets)
            ->whereInstanceOf(MonologTarget::class)
            ->each(function(MonologTarget $target) {
                $target->enabled = $target->name === Dispatcher::TARGET_QUEUE;
            });
    }

    /**
     * Returns the job execution time in seconds.
     *
     * @return string
     */
    private function _formattedDuration(): string
    {
        return sprintf('%.3f', microtime(true) - $this->_jobStartedAt) . 's';
    }
}
