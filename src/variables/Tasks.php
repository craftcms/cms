<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\base\Task;
use craft\app\base\TaskInterface;

/**
 * Task functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Tasks
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the currently running task.
	 *
	 * @return TaskInterface|Task|null The currently running task, or null if there isn’t one
	 */
	public function getRunningTask()
	{
		return \Craft::$app->tasks->getRunningTask();
	}

	/**
	 * Returns whether there is a task that is currently running.
	 *
	 * @return boolean Whether there is a task that is currently running
	 */
	public function isTaskRunning()
	{
		return \Craft::$app->tasks->isTaskRunning();
	}

	/**
	 * Returns whether there are any pending tasks, optionally by a given type.
	 *
	 * @param string|null $type The task type to check for, if any
	 * @return boolean Whether there are any pending tasks
	 */
	public function areTasksPending($type = null)
	{
		return \Craft::$app->tasks->areTasksPending($type);
	}

	/**
	 * Returns whether any tasks that have failed.
	 *
	 * @return boolean Whether any tasks have failed
	 */
	public function haveTasksFailed()
	{
		return \Craft::$app->tasks->haveTasksFailed();
	}

	/**
	 * Returns the total number of active tasks.
	 *
	 * @return integer The total number of active tasks
	 */
	public function getTotalTasks()
	{
		return \Craft::$app->tasks->getTotalTasks();
	}
}
