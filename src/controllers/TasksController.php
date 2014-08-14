<?php
namespace Craft;

/**
 * The TasksController class is a controller that handles various task related operations such as running, checking task
 * status, re-running and deleting tasks.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     2.0
 */
class TasksController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * Runs any pending tasks.
	 *
	 * @return null
	 */
	public function actionRunPendingTasks()
	{
		$this->requireAjaxRequest();
		craft()->userSession->requirePermission('accessCp');

		// If there's already a running task, treat this like a getRunningTaskInfo request
		$this->_returnRunningTaskInfo();

		// Apparently not. Is there a pending task?
		$task = craft()->tasks->getNextPendingTask();

		if ($task)
		{
			// Return info about the next pending task without stopping PHP execution
			JsonHelper::sendJsonHeaders();
			craft()->request->close(JsonHelper::encode($task->getInfo()));

			// Start running tasks
			craft()->tasks->runPendingTasks();
		}

		craft()->end();
	}

	/**
	 * Returns the completion percentage for the running task.
	 *
	 * @return null
	 */
	public function actionGetRunningTaskInfo()
	{
		$this->requireAjaxRequest();
		craft()->userSession->requirePermission('accessCp');

		$this->_returnRunningTaskInfo();

		// No running tasks left? Check for a failed one
		if (craft()->tasks->haveTasksFailed())
		{
			$this->returnJson(array('status' => 'error'));
		}

		craft()->end();
	}

	/**
	 * Re-runs a failed task.
	 *
	 * @return null
	 */
	public function actionRerunTask()
	{
		$this->requireAjaxRequest();
		$this->requirePostRequest();
		craft()->userSession->requirePermission('accessCp');

		$taskId = craft()->request->getRequiredPost('taskId');
		$task = craft()->tasks->rerunTaskById($taskId);

		if (!craft()->tasks->isTaskRunning())
		{
			JsonHelper::sendJsonHeaders();
			craft()->request->close(JsonHelper::encode($task->getInfo()));

			craft()->tasks->runPendingTasks();
		}
		else
		{
			$this->returnJson($task->getInfo());
		}

		craft()->end();
	}

	/**
	 * Deletes a task.
	 *
	 * @return null
	 */
	public function actionDeleteTask()
	{
		$this->requireAjaxRequest();
		$this->requirePostRequest();
		craft()->userSession->requirePermission('accessCp');

		$taskId = craft()->request->getRequiredPost('taskId');
		$task = craft()->tasks->deleteTaskById($taskId);

		craft()->end();
	}

	/**
	 * Returns info about all the tasks.
	 *
	 * @return null
	 */
	public function actionGetTaskInfo()
	{
		$this->requireAjaxRequest();
		craft()->userSession->requirePermission('accessCp');

		$tasks = craft()->tasks->getAllTasks();
		$taskInfo = array();

		foreach ($tasks as $task)
		{
			$taskInfo[] = $task->getInfo();
		}

		$this->returnJson($taskInfo);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns info about the currently running task, if there is one.
	 *
	 * @return null
	 */
	private function _returnRunningTaskInfo()
	{
		if ($task = craft()->tasks->getRunningTask())
		{
			$this->returnJson($task->getInfo());
		}
	}
}
