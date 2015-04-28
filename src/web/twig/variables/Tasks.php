<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

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
		return \Craft::$app->getTasks()->getRunningTask();
	}

	/**
	 * Returns whether there is a task that is currently running.
	 *
	 * @return boolean Whether there is a task that is currently running
	 */
	public function isTaskRunning()
	{
		return \Craft::$app->getTasks()->isTaskRunning();
	}

	/**
	 * Returns whether there are any pending tasks, optionally by a given type.
	 *
	 * @param string|null $type The task type to check for, if any
	 * @return boolean Whether there are any pending tasks
	 */
	public function areTasksPending($type = null)
	{
		return \Craft::$app->getTasks()->areTasksPending($type);
	}

	/**
	 * Returns whether any tasks that have failed.
	 *
	 * @return boolean Whether any tasks have failed
	 */
	public function haveTasksFailed()
	{
		return \Craft::$app->getTasks()->haveTasksFailed();
	}

	/**
	 * Returns the total number of active tasks.
	 *
	 * @return integer The total number of active tasks
	 */
	public function getTotalTasks()
	{
		return \Craft::$app->getTasks()->getTotalTasks();
	}
}
