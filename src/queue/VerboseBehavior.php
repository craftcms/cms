<?php namespace craft\queue;

use yii\queue\cli\VerboseBehavior as VerboseBehaviorBase;
use yii\queue\ExecEvent;


/**
 * Verbose Behavior
 */
class VerboseBehavior extends VerboseBehaviorBase
{
    /**
     * @param \yii\queue\ExecEvent $event
     *
     * @return string
     */
    protected function jobTitle(ExecEvent $event)
    {
        if (!$event->job instanceof JobInterface) {
            return parent::jobTitle($event);
        }

        $description = $event->job->getDescription();
        $extra = "attempt: $event->attempt";
        if ($pid = $event->sender->getWorkerPid()) {
            $extra .= ", pid: $pid";
        }
        return " [$event->id] $description ($extra)";
    }
}
