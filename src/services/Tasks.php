<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\enums\TaskStatus;
use craft\app\models\Task as TaskModel;
use craft\app\records\Task as TaskRecord;
use yii\base\Component;

/**
 * Class Tasks service.
 *
 * An instance of the Tasks service is globally accessible in Craft via [[Application::tasks `Craft::$app->tasks`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Tasks extends Component
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_taskRecordsById;

	/**
	 * @var
	 */
	private $_nextPendingTask;

	/**
	 * @var
	 */
	private $_runningTask;

	/**
	 * @var
	 */
	private $_listeningForRequestEnd = false;

	// Public Methods
	// =========================================================================

	/**
	 * Creates a task to run later in the system.
	 *
	 * @param string      $type
	 * @param string|null $description
	 * @param array|null  $settings
	 * @param int|null    $parentId
	 *
	 * @throws \Exception
	 * @return TaskModel
	 */
	public function createTask($type, $description = null, $settings = [], $parentId = null)
	{
		$task = new TaskModel();
		$task->type = $type;
		$task->description = $description;
		$task->settings = $settings;
		$task->parentId = $parentId;
		$this->saveTask($task);

		if (!$this->_listeningForRequestEnd && !$this->isTaskRunning())
		{
			// Turn this request into a runner once everything else is done
			Craft::$app->attachEventHandler('onEndRequest', [$this, 'closeAndRun']);
			$this->_listeningForRequestEnd = true;
		}

		return $task;
	}

	/**
	 * Saves a task.
	 *
	 * @param TaskModel $task
	 * @param bool      $validate
	 *
	 * @return bool
	 */
	public function saveTask(TaskModel $task, $validate = true)
	{
		if ($task->isNew())
		{
			$taskRecord = new TaskRecord();
		}
		else
		{
			$taskRecord = $this->_getTaskRecordById($task->id);
		}

		$taskRecord->type        = $task->type;
		$taskRecord->status      = $task->status;
		$taskRecord->settings    = $task->settings;
		$taskRecord->description = $task->description;
		$taskRecord->totalSteps  = $task->totalSteps;
		$taskRecord->currentStep = $task->currentStep;

		if (!$task->parentId || !$task->isNew())
		{
			$success = $taskRecord->saveNode($validate);
		}
		else
		{
			$parentTaskRecord = $this->_getTaskRecordById($task->parentId);
			$success = $taskRecord->appendTo($parentTaskRecord, $validate);
		}

		if ($success)
		{
			if ($task->isNew())
			{
				$task->id = $taskRecord->id;

				if ($task->parentId)
				{
					// We'll be needing this soon
					$this->_taskRecordsById[$taskRecord->id] = $taskRecord;
				}
			}

			return true;
		}
		else
		{
			$task->addErrors($taskRecord->getErrors());
			return false;
		}
	}

	/**
	 * Closes the connection with the client and turns the request into a task runner.
	 *
	 * @return null
	 */
	public function closeAndRun()
	{
		// Make sure a future call to Craft::$app->end() dosen't trigger this a second time
		Craft::$app->detachEventHandler('onEndRequest', [$this, 'closeAndRun']);

		// Make sure nothing has been output to the browser yet
		if (!headers_sent())
		{
			// Close the client connection
			Craft::$app->getRequest()->close();

			// Run any pending tasks
			$this->runPendingTasks();
		}
	}

	/**
	 * Re-runs a task by a given ID.
	 *
	 * @param int $taskId
	 *
	 * @return TaskModel|null
	 */
	public function rerunTaskById($taskId)
	{
		$task = $this->getTaskById($taskId);

		if ($task && $task->level == 0)
		{
			$task->currentStep = null;
			$task->totalSteps = null;
			$task->status = TaskStatus::Pending;
			$this->saveTask($task);

			// Delete any of its subtasks
			$taskRecord = $this->_getTaskRecordById($taskId);
			$subtaskRecords = $taskRecord->descendants()->findAll();

			foreach ($subtaskRecords as $subtaskRecord)
			{
				$subtaskRecord->deleteNode();
			}

			return $task;
		}
	}

	/**
	 * Runs any pending tasks.
	 *
	 * @return null
	 */
	public function runPendingTasks()
	{
		// If we're already processing tasks, let's give it a break.
		if ($this->isTaskRunning())
		{
			Craft::info('Tasks are already running.', __METHOD__);
			return;
		}

		// It's go time.
		Craft::$app->config->maxPowerCaptain();

		while ($task = $this->getNextPendingTask())
		{
			$this->_runningTask = $task;
			$this->runTask($task);
		}

		$this->_runningTask = null;
	}

	/**
	 * Runs a given task.
	 *
	 * @param TaskModel $task
	 *
	 * @return bool
	 */
	public function runTask(TaskModel $task)
	{
		$error = null;

		try
		{
			$taskRecord = $this->_getTaskRecordById($task->id);
			$taskType = $task->getTaskType();

			if ($taskType)
			{
				// Figure out how many total steps there are.
				$task->totalSteps = $taskType->getTotalSteps();
				$task->status = TaskStatus::Running;

				Craft::log('Starting task '.$taskRecord->type.' that has a total of '.$task->totalSteps.' steps.');

				for ($step = 0; $step < $task->totalSteps; $step++)
				{
					// Update the task
					$task->currentStep = $step+1;
					$this->saveTask($task);

					Craft::log('Starting step '.($step+1).' of '.$task->totalSteps.' total steps.');

					// Run it.
					if (($result = $taskType->runStep($step)) !== true)
					{
						// Did they give us an error to report?
						if (is_string($result))
						{
							$error = $result;
						}
						else
						{
							$error = true;
						}

						break;
					}
				}
			}
			else
			{
				$error = 'Could not find the task component type.';
			}
		}
		catch (\Exception $e)
		{
			$error = 'An exception was thrown: '.$e->getMessage();
		}

		if ($task == $this->_nextPendingTask)
		{
			// Don't run this again
			$this->_nextPendingTask = null;
		}

		if ($error === null)
		{
			Craft::info('Finished task '.$task->id.' ('.$task->type.').', __METHOD__);

			// We're done with this task, nuke it.
			$taskRecord->deleteNode();

			return true;
		}
		else
		{
			$this->fail($task, $error);
			return false;
		}
	}

	/**
	 * Sets a task's status to "error" and logs it.
	 *
	 * @param TaskModel $task
	 * @param mixed     $error
	 *
	 * @return null
	 */
	public function fail(TaskModel $task, $error = null)
	{
		$task->status = TaskStatus::Error;
		$this->saveTask($task);

		// Log it
		$logMessage = 'Encountered an error running task '.$task->id.' ('.$task->type.')';

		if ($task->currentStep)
		{
			$logMessage .= ', step '.$task->currentStep;

			if ($task->totalSteps)
			{
				$logMessage .= ' of '.$task->totalSteps;
			}
		}

		if ($error && is_string($error))
		{
			$logMessage .= ': '.$error;
		}
		else
		{
			$logMessage .= '.';
		}

		Craft::error($logMessage, __METHOD__);
	}

	/**
	 * Returns a task by its ID.
	 *
	 * @param int $taskId
	 *
	 * @return TaskModel|null
	 */
	public function getTaskById($taskId)
	{
		$result = (new Query())
			->select('*')
			->from('{{%tasks}}')
			->where('id = :id', [':id' => $taskId])
			->one();

		if ($result)
		{
			return TaskModel::populateModel($result);
		}
	}

	/**
	 * Returns all the tasks.
	 *
	 * @return TaskModel[]
	 */
	public function getAllTasks()
	{
		$results = (new Query())
			->select('*')
			->from('{{%tasks}}')
			->orderBy('root asc, lft asc')
			->all();

		return TaskModel::populateModels($results);
	}

	/**
	 * Returns the currently running task.
	 *
	 * @return TaskModel|null
	 */
	public function getRunningTask()
	{
		if (!isset($this->_runningTask))
		{
			$result = (new Query())
				->select('*')
				->from('{{%tasks}}')
				->where(
					['and', 'lft = 1', 'status = :status'/*, 'dateUpdated >= :aMinuteAgo'*/],
					[':status' => TaskStatus::Running/*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/]
				)
				->one();

			if ($result)
			{
				$this->_runningTask = TaskModel::populateModel($result);
			}
			else
			{
				$this->_runningTask = false;
			}
		}

		if ($this->_runningTask)
		{
			return $this->_runningTask;
		}
	}

	/**
	 * Returns whether there is a task that is currently running.
	 *
	 * @return bool
	 */
	public function isTaskRunning()
	{
		// Remember that a root task could appear to be stagnant if it has sub-tasks.
		return (new Query())
			->from('{{%tasks}}')
			->where(
				['and','status = :status'/*, 'dateUpdated >= :aMinuteAgo'*/],
				[':status' => TaskStatus::Running/*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/]
			)
			->exists();
	}

	/**
	 * Returns whether there are any pending tasks, optionally by a given type.
	 *
	 * @param string|null $type
	 *
	 * @return bool
	 */
	public function areTasksPending($type = null)
	{
		$conditions = ['and', 'lft = 1', 'status = :status'];
		$params     = [':status' => TaskStatus::Pending];

		if ($type)
		{
			$conditions[] = 'type = :type';
			$params[':type'] = $type;
		}

		return (new Query())
			->from('{{%tasks}}')
			->where($conditions, $params)
			->exists();
	}

	/**
	 * Returns any pending tasks, optionally by a given type.
	 *
	 * @param string|null $type
	 * @param int|null    $limit
	 *
	 * @return TaskModel[]
	 */
	public function getPendingTasks($type = null, $limit = null)
	{
		$conditions = ['and', 'lft = 1', 'status = :status'];
		$params     = [':status' => TaskStatus::Pending];

		if ($type)
		{
			$conditions[] = 'type = :type';
			$params[':type'] = $type;
		}

		$query = (new Query())
			->from('{{%tasks}}')
			->where($conditions, $params);

		if ($limit)
		{
			$query->limit($limit);
		}

		$results = $query->all();
		return TaskModel::populateModels($results);
	}

	/**
	 * Returns whether any tasks that have failed.
	 *
	 * @return bool
	 */
	public function haveTasksFailed()
	{
		return (new Query())
			->from('{{%tasks}}')
			->where(['and', 'level = 0', 'status = :status'], [':status' => TaskStatus::Error])
			->exists();
	}

	/**
	 * Returns the total number of active tasks.
	 *
	 * @return bool
	 */
	public function getTotalTasks()
	{
		return (new Query())
			->from('{{%tasks}}')
			->where(
				['and', 'lft = 1', 'status != :status'],
				[':status' => TaskStatus::Error]
			)
			->count('id');
	}

	/**
	 * Returns the next pending task.
	 *
	 * @param string|null $type
	 *
	 * @return TaskModel|null|false
	 */
	public function getNextPendingTask($type = null)
	{
		// If a type was passed, we don't need to actually save it, as it's probably not an actual task-running request.
		if ($type)
		{
			$pendingTasks = $this->getPendingTasks($type, 1);

			if ($pendingTasks)
			{
				return $pendingTasks[0];
			}
		}
		else
		{
			if (!isset($this->_nextPendingTask))
			{
				$taskRecord = TaskRecord::model()->roots()->ordered()->findByAttributes([
					'status' => TaskStatus::Pending
				]);

				if ($taskRecord)
				{
					$this->_taskRecordsById[$taskRecord->id] = $taskRecord;
					$this->_nextPendingTask = TaskModel::populateModel($taskRecord);
				}
				else
				{
					$this->_nextPendingTask = false;
				}
			}

			if ($this->_nextPendingTask)
			{
				return $this->_nextPendingTask;
			}
		}
	}

	/**
	 * Deletes a task by its ID.
	 *
	 * @param int $taskId
	 *
	 * @return bool
	 */
	public function deleteTaskById($taskId)
	{
		$taskRecord = $this->_getTaskRecordById($taskId);
		$success = $taskRecord->deleteNode();
		unset($this->_taskRecordsById[$taskId]);

		return $success;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a task by its ID.
	 *
	 * @param int $taskId
	 *
	 * @return TaskRecord|null|false
	 */
	private function _getTaskRecordById($taskId)
	{
		if (!isset($this->_taskRecordsById[$taskId]))
		{
			$this->_taskRecordsById[$taskId] = TaskRecord::model()->findById($taskId);

			if (!$this->_taskRecordsById[$taskId])
			{
				$this->_taskRecordsById[$taskId] = false;
			}
		}

		if ($this->_taskRecordsById[$taskId])
		{
			return $this->_taskRecordsById[$taskId];
		}
	}
}
