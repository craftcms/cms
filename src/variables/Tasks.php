<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\models\Task as TaskModel;

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
	 * @return TaskModel|null
	 */
	public function getRunningTask()
	{
		return craft()->tasks->getRunningTask();
	}

	/**
	 * Returns whether there is a task that is currently running.
	 *
	 * @return bool
	 */
	public function isTaskRunning()
	{
		return craft()->tasks->isTaskRunning();
	}

	/**
	 * Returns whether there are any pending tasks.
	 *
	 * @return bool
	 */
	public function areTasksPending()
	{
		return craft()->tasks->areTasksPending();
	}

	/**
	 * Returns whether any tasks that have failed.
	 *
	 * @return bool
	 */
	public function haveTasksFailed()
	{
		return craft()->tasks->haveTasksFailed();
	}

	/**
	 * Returns the total number of active tasks.
	 *
	 * @return bool
	 */
	public function getTotalTasks()
	{
		return craft()->tasks->getTotalTasks();
	}
}
