<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\helpers\JsonHelper;
use craft\app\web\Controller;

/**
 * The TasksController class is a controller that handles various task related operations such as running, checking task
 * status, re-running and deleting tasks.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TasksController extends Controller
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
		$this->requirePermission('accessCp');

		// If there's already a running task, treat this like a getRunningTaskInfo request
		$this->_returnRunningTaskInfo();

		// Apparently not. Is there a pending task?
		$task = Craft::$app->tasks->getNextPendingTask();

		if ($task)
		{
			// Return info about the next pending task without stopping PHP execution
			JsonHelper::sendJsonHeaders();
			$response = Craft::$app->getResponse();
			$response->content = JsonHelper::encode($task);
			$response->sendAndClose();

			// Start running tasks
			Craft::$app->tasks->runPendingTasks();
		}

		Craft::$app->end();
	}

	/**
	 * Returns the completion percentage for the running task.
	 *
	 * @return null
	 */
	public function actionGetRunningTaskInfo()
	{
		$this->requireAjaxRequest();
		$this->requirePermission('accessCp');

		$this->_returnRunningTaskInfo();

		// No running tasks left? Check for a failed one
		if (Craft::$app->tasks->haveTasksFailed())
		{
			$this->returnJson(['status' => 'error']);
		}

		Craft::$app->end();
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
		$this->requirePermission('accessCp');

		$taskId = Craft::$app->getRequest()->getRequiredBodyParam('taskId');
		$task = Craft::$app->tasks->rerunTaskById($taskId);

		if (!Craft::$app->tasks->isTaskRunning())
		{
			JsonHelper::sendJsonHeaders();
			$response = Craft::$app->getResponse();
			$response->content = JsonHelper::encode($task);
			$response->sendAndClose();

			Craft::$app->tasks->runPendingTasks();
		}
		else
		{
			$this->returnJson($task);
		}

		Craft::$app->end();
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
		$this->requirePermission('accessCp');

		$taskId = Craft::$app->getRequest()->getRequiredBodyParam('taskId');
		Craft::$app->tasks->deleteTaskById($taskId);

		Craft::$app->end();
	}

	/**
	 * Returns info about all the tasks.
	 *
	 * @return null
	 */
	public function actionGetTaskInfo()
	{
		$this->requireAjaxRequest();
		$this->requirePermission('accessCp');
		$this->returnJson(Craft::$app->tasks->getAllTasks());
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
		if ($task = Craft::$app->tasks->getRunningTask())
		{
			$this->returnJson($task);
		}
	}
}
