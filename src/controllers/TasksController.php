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
	// Properties
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected $allowAnonymous = ['actionRunPendingTasks'];

	// Public Methods
	// =========================================================================

	/**
	 * Runs any pending tasks.
	 *
	 * @return null
	 */
	public function actionRunPendingTasks()
	{
		$tasksService = Craft::$app->getTasks();

		// Make sure tasks aren't already running
		if (!$tasksService->isTaskRunning())
		{
			$task = $tasksService->getNextPendingTask();

			if ($task)
			{
				// Attempt to close the connection if this is an Ajax request
				if (Craft::$app->getRequest()->getIsAjax())
				{
					Craft::$app->getResponse()->sendAndClose();
				}

				// Start running tasks
				$tasksService->runPendingTasks();
			}
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

		$tasksService = Craft::$app->getTasks();

		if ($task = $tasksService->getRunningTask())
		{
			return $this->asJson($task);
		}

		// No running tasks left? Check for a failed one
		if ($tasksService->haveTasksFailed())
		{
			return $this->asJson(['status' => 'error']);
		}

		// Any pending tasks?
		if ($task = $tasksService->getNextPendingTask())
		{
			return $this->asJson($task);
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
		$task = Craft::$app->getTasks()->rerunTaskById($taskId);

		if (!Craft::$app->getTasks()->isTaskRunning())
		{
			JsonHelper::sendJsonHeaders();
			$response = Craft::$app->getResponse();
			$response->content = JsonHelper::encode($task);
			$response->sendAndClose();

			Craft::$app->getTasks()->runPendingTasks();
		}
		else
		{
			return $this->asJson($task);
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
		Craft::$app->getTasks()->deleteTaskById($taskId);

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
		return $this->asJson(Craft::$app->getTasks()->getAllTasks());
	}
}
