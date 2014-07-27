<?php
namespace Craft;

/**
 * Task functions.
 *
 * @package craft.app.validators
 */
class TasksVariable
{
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
