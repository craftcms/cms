<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Task;
use craft\app\base\TaskInterface;
use craft\app\db\Query;
use craft\app\errors\InvalidComponentException;
use craft\app\helpers\ComponentHelper;
use craft\app\records\Task as TaskRecord;
use craft\app\tasks\InvalidTask;
use yii\base\Application;
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
	// Constants
	// =========================================================================

	/**
	 * @var string The task interface name
	 */
	const TASK_INTERFACE = 'craft\app\base\TaskInterface';

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
	 * Saves a new task and queues it up to be run at the earliest opportunity.
	 *
	 * @param TaskInterface|Task|array|string $task The task, the task’s class name, or its config, with a `type` value and optionally a `settings` value
	 *
	 * @throws \Exception
	 * @return TaskInterface|Task The task
	 */
	public function queueTask($task)
	{
		if (!$task instanceof TaskInterface)
		{
			$task = $this->createTask($task);
		}

		$this->saveTask($task);

		if (!$this->_listeningForRequestEnd && !$this->isTaskRunning())
		{
			// Turn this request into a runner once everything else is done
			Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'closeAndRun']);
			$this->_listeningForRequestEnd = true;
		}

		return $task;
	}

	/**
	 * Creates a task with a given config.
	 *
	 * @param mixed $config The task’s class name, or its config, with a `type` value and optionally a `settings` value
	 * @return TaskInterface|Task The task
	 */
	public function createTask($config)
	{
		if (is_string($config))
		{
			$config = ['type' => $config];
		}

		try
		{
			return ComponentHelper::createComponent($config, self::TASK_INTERFACE);
		}
		catch (InvalidComponentException $e)
		{
			$config['errorMessage'] = $e->getMessage();
			return InvalidTask::create($config);
		}
	}

	/**
	 * Saves a task.
	 *
	 * @param TaskInterface|Task $task    The task to be saved
	 * @param boolean            $validate Whether the task should be validated first
	 * @return boolean Whether the task was saved successfully
	 * @throws \Exception
	 */
	public function saveTask(TaskInterface $task, $validate = true)
	{
		if (!$validate || $task->validate())
		{
			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
			try
			{
				if ($task->isNew())
				{
					$taskRecord = new TaskRecord();
				}
				else
				{
					$taskRecord = $this->_getTaskRecordById($task->id);
				}

				$taskRecord->type        = $task->getType();
				$taskRecord->status      = $task->status;
				$taskRecord->description = $task->description;
				$taskRecord->totalSteps  = $task->totalSteps;
				$taskRecord->currentStep = $task->currentStep;
				$taskRecord->settings    = $task->getSettings();

				if (!$task->isNew())
				{
					$taskRecord->save(false);
				}
				else if (!$task->parentId)
				{
					$taskRecord->makeRoot(false);
				}
				else
				{
					$parentTaskRecord = $this->_getTaskRecordById($task->parentId);
					$taskRecord->appendTo($parentTaskRecord, false);
				}

				if ($task->isNew())
				{
					$task->id = $taskRecord->id;

					if ($task->parentId)
					{
						// We'll be needing this soon
						$this->_taskRecordsById[$taskRecord->id] = $taskRecord;
					}
				}

				if ($transaction !== null)
				{
					$transaction->commit();
				}

				return true;
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Closes the connection with the client and turns the request into a task runner.
	 */
	public function closeAndRun()
	{
		// Make sure nothing has been output to the browser yet
		if (!headers_sent())
		{
			// Close the client connection
			Craft::$app->getResponse()->sendAndClose();

			// Run any pending tasks
			$this->runPendingTasks();
		}
	}

	/**
	 * Re-runs a task by a given ID.
	 *
	 * @param int $taskId The task’s ID
	 * @return TaskInterface|Task|null The task
	 */
	public function rerunTaskById($taskId)
	{
		$task = $this->getTaskById($taskId);

		if ($task && $task->level == 0)
		{
			$task->currentStep = null;
			$task->totalSteps = null;
			$task->status = Task::STATUS_PENDING;
			$this->saveTask($task);

			// Delete any of its subtasks
			$taskRecord = $this->_getTaskRecordById($taskId);
			$subtaskRecords = $taskRecord->children()->all();

			foreach ($subtaskRecords as $subtaskRecord)
			{
				$subtaskRecord->deleteWithChildren();
			}

			return $task;
		}
	}

	/**
	 * Runs any pending tasks.
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
	 * @param TaskInterface|Task $task
	 * @return bool
	 */
	public function runTask(TaskInterface $task)
	{
		$taskRecord = $this->_getTaskRecordById($task->id);
		$error = null;

		if ($task instanceof InvalidTask)
		{
			$error = $task->errorMessage;
		}
		else
		{
			try
			{
				// Figure out how many total steps there are.
				$task->totalSteps = $task->getTotalSteps();
				$task->status = Task::STATUS_RUNNING;

				Craft::info('Starting task '.$taskRecord->type.' that has a total of '.$task->totalSteps.' steps.');

				for ($step = 0; $step < $task->totalSteps; $step++)
				{
					// Update the task
					$task->currentStep = $step + 1;
					$this->saveTask($task);

					Craft::info('Starting step '.($step + 1).' of '.$task->totalSteps.' total steps.');

					// Run it.
					if (($result = $task->runStep($step)) !== true)
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
			catch (\Exception $e)
			{
				$error = 'An exception was thrown: '.$e->getMessage();
			}
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
			$taskRecord->deleteWithChildren();

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
	 * @param TaskInterface|Task $task  The task
	 * @param string|null        $error The error message
	 *
	 * @return null
	 */
	public function fail(TaskInterface $task, $error = null)
	{
		$task->status = Task::STATUS_ERROR;
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
	 * @param int $taskId The task’s ID
	 * @return TaskInterface|Task|null The task, or null if it doesn’t exist
	 */
	public function getTaskById($taskId)
	{
		$result = (new Query())
			->select('*')
			->from('{{%tasks}}')
			->where('id = :id', [':id' => $taskId])
			->one();

		if ($result !== false)
		{
			return $this->createTask($result);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Returns all the tasks.
	 *
	 * @return TaskInterface[]|Task[] All the tasks
	 */
	public function getAllTasks()
	{
		$tasks = (new Query())
			->select('*')
			->from('{{%tasks}}')
			->orderBy('root asc, lft asc')
			->all();

		foreach ($tasks as $key => $value)
		{
			$tasks[$key] = $this->createTask($value);
		}

		return $tasks;
	}

	/**
	 * Returns the currently running task.
	 *
	 * @return TaskInterface|Task|null The currently running task, or null if there isn’t one
	 */
	public function getRunningTask()
	{
		if ($this->_runningTask === null)
		{
			$result = (new Query())
				->select('*')
				->from('{{%tasks}}')
				->where(
					['and', 'lft = 1', 'status = :status'/*, 'dateUpdated >= :aMinuteAgo'*/],
					[':status' => Task::STATUS_RUNNING/*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/]
				)
				->one();

			if ($result !== false)
			{
				$this->_runningTask = $this->createTask($result);
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
		else
		{
			return null;
		}
	}

	/**
	 * Returns whether there is a task that is currently running.
	 *
	 * @return boolean Whether there is a task that is currently running
	 */
	public function isTaskRunning()
	{
		// Remember that a root task could appear to be stagnant if it has sub-tasks.
		return (new Query())
			->from('{{%tasks}}')
			->where(
				['and','status = :status'/*, 'dateUpdated >= :aMinuteAgo'*/],
				[':status' => Task::STATUS_RUNNING/*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/]
			)
			->exists();
	}

	/**
	 * Returns whether there are any pending tasks, optionally by a given type.
	 *
	 * @param string|null $type The task type to check for, if any
	 * @return boolean Whether there are any pending tasks
	 */
	public function areTasksPending($type = null)
	{
		$conditions = ['and', 'lft = 1', 'status = :status'];
		$params     = [':status' => Task::STATUS_PENDING];

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
	 * @param string|null  $type  The task type to check for, if any
	 * @param integer|null $limit The maximum number of tasks to return
	 * @return TaskInterface[]|Task[] The pending tasks
	 */
	public function getPendingTasks($type = null, $limit = null)
	{
		$conditions = ['and', 'lft = 1', 'status = :status'];
		$params     = [':status' => Task::STATUS_PENDING];

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

		$tasks = $query->all();

		foreach ($tasks as $key => $value)
		{
			$tasks[$key] = $this->createTask($value);
		}

		return $tasks;
	}

	/**
	 * Returns whether any tasks that have failed.
	 *
	 * @return boolean Whether any tasks have failed
	 */
	public function haveTasksFailed()
	{
		return (new Query())
			->from('{{%tasks}}')
			->where(['and', 'level = 0', 'status = :status'], [':status' => Task::STATUS_ERROR])
			->exists();
	}

	/**
	 * Returns the total number of active tasks.
	 *
	 * @return integer The total number of active tasks
	 */
	public function getTotalTasks()
	{
		return (new Query())
			->from('{{%tasks}}')
			->where(
				['and', 'lft = 1', 'status != :status'],
				[':status' => Task::STATUS_ERROR]
			)
			->count('id');
	}

	/**
	 * Returns the next pending task.
	 *
	 * @param string|null $type The type of task to check for, if any
	 * @return TaskInterface|Task|null The next pending task, if any
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
			if ($this->_nextPendingTask === null)
			{
				$taskRecord = TaskRecord::find()
					->where(['status' => Task::STATUS_PENDING])
					->orderBy('dateCreated')
					->roots()
					->one();

				if ($taskRecord)
				{
					$this->_taskRecordsById[$taskRecord->id] = $taskRecord;
					$this->_nextPendingTask = $this->createTask($taskRecord);
				}
				else
				{
					$this->_nextPendingTask = false;
				}
			}

			if ($this->_nextPendingTask !== false)
			{
				return $this->_nextPendingTask;
			}
			else
			{
				return null;
			}
		}
	}

	/**
	 * Deletes a task by its ID.
	 *
	 * @param int $taskId The task’s ID
	 * @return boolean Whether the task was deleted successfully
	 */
	public function deleteTaskById($taskId)
	{
		$taskRecord = $this->_getTaskRecordById($taskId);

		if ($taskRecord === null)
		{
			// Fake it
			return true;
		}

		$success = $taskRecord->deleteWithChildren();
		unset($this->_taskRecordsById[$taskId]);
		return $success;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a TaskRecord by its ID.
	 *
	 * @param int $taskId The task’s ID
	 * @return TaskRecord|null The TaskRecord, or null if it doesn’t exist
	 */
	private function _getTaskRecordById($taskId)
	{
		if (!isset($this->_taskRecordsById[$taskId]))
		{
			$this->_taskRecordsById[$taskId] = TaskRecord::findOne($taskId);

			if ($this->_taskRecordsById[$taskId] === null)
			{
				$this->_taskRecordsById[$taskId] = false;
			}
		}

		if ($this->_taskRecordsById[$taskId] !== false)
		{
			return $this->_taskRecordsById[$taskId];
		}
		else
		{
			return null;
		}
	}
}
