<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use yii\queue\ExecEvent;

/**
 * Verbose Behavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class VerboseBehavior extends \yii\queue\cli\VerboseBehavior
{
    /**
     *
     */
    protected function jobTitle(ExecEvent $event)
    {
        if (!$event->job instanceof JobInterface) {
            return parent::jobTitle($event);
        }

        $description = $event->job->getDescription();
        $extra = 'attempt: ' . $event->attempt;

        if ($pid = $event->sender->getWorkerPid()) {
            $extra .= ', pid: ' . $pid;
        }

        return " [{$event->id}] {$description} ({$extra})";
    }
}
