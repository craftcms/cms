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
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     2.0
 */
class TasksController extends BaseController
{
	// Properties
	// =========================================================================

	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 *
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 *
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in
	 * the array list.
	 *
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require
	 * login on a few, it's best to use {@link UserSessionService::requireLogin() craft()->userSession->requireLogin()}
	 * in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = array('actionRunPendingTasks');

	// Public Methods
	// =========================================================================

	/**
	 * Runs any pending tasks.
	 *
	 * @return null
	 */
	public function actionRunPendingTasks()
	{
		// Make sure tasks aren't already running
		if (!craft()->tasks->isTaskRunning())
		{
			// Is there a pending task?
			$task = craft()->tasks->getNextPendingTask();

			if ($task)
			{
				// Attempt to close the connection if this is an Ajax request
				if (craft()->request->isAjaxRequest())
				{
					craft()->request->close();
				}

				// Start running tasks
				craft()->tasks->runPendingTasks();
			}
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

		if ($task = craft()->tasks->getRunningTask())
		{
			$this->returnJson($task->getInfo());
		}

		// No running tasks left? Check for a failed one
		if (craft()->tasks->haveTasksFailed())
		{
			$this->returnJson(array('status' => 'error'));
		}

		// Any pending tasks?
		if ($task = craft()->tasks->getNextPendingTask())
		{
			$this->returnJson($task->getInfo());
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
}
