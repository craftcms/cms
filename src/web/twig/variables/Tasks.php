<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\base\TaskInterface;

/**
 * Task functions.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class Tasks
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the currently running task.
     *
     * @return TaskInterface|null The currently running task, or null if there isn’t one
     */
    public function getRunningTask()
    {
        Craft::$app->getDeprecator()->log('craft.tasks.getRunningTask()', 'craft.tasks.getRunningTask() has been deprecated. Use craft.app.tasks.runningTask instead.');

        return Craft::$app->getTasks()->getRunningTask();
    }

    /**
     * Returns whether there is a task that is currently running.
     *
     * @return boolean Whether there is a task that is currently running
     */
    public function isTaskRunning()
    {
        Craft::$app->getDeprecator()->log('craft.tasks.isTaskRunning()', 'craft.tasks.isTaskRunning() has been deprecated. Use craft.app.tasks.isTaskRunning instead.');

        return Craft::$app->getTasks()->getIsTaskRunning();
    }

    /**
     * Returns whether there are any pending tasks, optionally by a given type.
     *
     * @param string|null $type The task type to check for, if any
     *
     * @return boolean Whether there are any pending tasks
     */
    public function areTasksPending($type = null)
    {
        Craft::$app->getDeprecator()->log('craft.tasks.areTasksPending()', 'craft.tasks.areTasksPending() has been deprecated. Use craft.app.tasks.areTasksPending() instead.');

        return Craft::$app->getTasks()->areTasksPending($type);
    }

    /**
     * Returns whether any tasks that have failed.
     *
     * @return boolean Whether any tasks have failed
     */
    public function haveTasksFailed()
    {
        Craft::$app->getDeprecator()->log('craft.tasks.haveTasksFailed()', 'craft.tasks.haveTasksFailed() has been deprecated. Use craft.app.tasks.haveTasksFailed instead.');

        return Craft::$app->getTasks()->getHaveTasksFailed();
    }

    /**
     * Returns the total number of active tasks.
     *
     * @return integer The total number of active tasks
     */
    public function getTotalTasks()
    {
        Craft::$app->getDeprecator()->log('craft.tasks.getTotalTasks()', 'craft.tasks.getTotalTasks() has been deprecated. Use craft.app.tasks.totalTasks instead.');

        return Craft::$app->getTasks()->getTotalTasks();
    }
}
