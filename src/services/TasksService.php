<?php
namespace Craft;

/**
 *
 */
class TasksService extends BaseApplicationComponent
{
	private $_taskRecordsById;
	private $_nextPendingTask;

	/**
	 * Creates a task to run later in the system.
	 *
	 * @param string      $type
	 * @param string|null $description
	 * @param array|null  $settings
	 * @param int|null    $parentId
	 * @throws \Exception
	 * @return TaskModel
	 */
	public function createTask($type, $description = null, $settings = array(), $parentId = null)
	{
		$task = new TaskModel();
		$task->type = $type;
		$task->description = $description;
		$task->settings = $settings;
		$task->parentId = $parentId;
		$this->saveTask($task);
		return $task;
	}

	/**
	 * Saves a task.
	 *
	 * @param TaskModel $task
	 * @param bool      $validate
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
	 * Re-runs a task by a given ID.
	 *
	 * @param $taskId
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
	 */
	public function runPendingTasks()
	{
		// If we're already processing tasks, let's give it a break.
		if ($this->isTaskRunning())
		{
			Craft::log('Tasks are already running.', LogLevel::Info, true);
			return;
		}

		// It's go time.
		craft()->config->maxPowerCaptain();

		while ($task = $this->getNextPendingTask())
		{
			$this->runTask($task);
		}
	}

	/**
	 * Runs a given task.
	 *
	 * @param TaskModel $task
	 * @return bool
	 * @throws \Exception
	 */
	public function runTask(TaskModel $task)
	{
		$success = true;

		try
		{
			$taskRecord = $this->_getTaskRecordById($task->id);
			$taskType = $task->getTaskType();

			if ($taskType)
			{
				// Figure out how many total steps there are.
				$task->totalSteps = $taskType->getTotalSteps();
				$task->status = TaskStatus::Running;

				Craft::Log('Starting task '.$taskRecord->type.' that has a total of '.$task->totalSteps.' steps.', LogLevel::Info, true);

				for ($step = 0; $step < $task->totalSteps; $step++)
				{
					// Update the task
					$task->currentStep = $step+1;
					$this->saveTask($task);

					Craft::Log('Starting step '.($step+1).' of '.$task->totalSteps.' total steps.', LogLevel::Info, true);

					// Run it.
					if (($result = $taskType->runStep($step)) !== true)
					{
						$message = 'Encountered an error running step '.($step+1).' of '.$task->totalSteps.' on '.$taskRecord->type.' with the ID "'.$task->id.'"';

						// Did they give us an error to report?
						if (is_string($result))
						{
							$message .= ': '.$result;
						}
						else
						{
							$message .= '.';
						}

						Craft::log($message, LogLevel::Error);
						$success = false;
						break;
					}
				}
			}
			else
			{
				Craft::log('Could not find the task component type for task '.$taskRecord->type, LogLevel::Error);
				$success = false;
			}
		}
		catch (\Exception $e)
		{
			Craft::log('Something went wrong when processing the tasks:'.$e->getMessage(), LogLevel::Error);
			$success = false;
		}

		if ($task == $this->_nextPendingTask)
		{
			// Don't run this again
			$this->_nextPendingTask = null;
		}

		if ($success)
		{
			Craft::log('Finished task '.$taskRecord->type.'.', LogLevel::Info, true);

			// We're done with this task, nuke it.
			$taskRecord->deleteNode();

			return true;
		}
		else
		{
			$task->status = TaskStatus::Error;
			$this->saveTask($task);

			return false;
		}
	}

	/**
	 * Returns a task by its ID.
	 *
	 * @param int $taskId
	 * @return TaskModel|null
	 */
	public function getTaskById($taskId)
	{
		$result = craft()->db->createCommand()
			->select('*')
			->from('tasks')
			->where('id = :id', array(':id' => $taskId))
			->queryRow();

		if ($result)
		{
			return TaskModel::populateModel($result);
		}
	}

	/**
	 * Returns all the tasks.
	 */
	public function getAllTasks()
	{
		$results = craft()->db->createCommand()
			->select('*')
			->from('tasks')
			->order('root asc, lft asc')
			->queryAll();

		return TaskModel::populateModels($results);
	}

	/**
	 * Returns the currently running task.
	 *
	 * @return TaskModel|null
	 */
	public function getRunningTask()
	{
		$result = craft()->db->createCommand()
			->select('*')
			->from('tasks')
			->where(
				array('and', 'lft = 1', 'status = :status'/*, 'dateUpdated >= :aMinuteAgo'*/),
				array(':status' => TaskStatus::Running/*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/)
			)
			->queryRow();

		if ($result)
		{
			return TaskModel::populateModel($result);
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
		return (bool) craft()->db->createCommand()
			->from('tasks')
			->where(
				array('and','status = :status'/*, 'dateUpdated >= :aMinuteAgo'*/),
				array(':status' => TaskStatus::Running/*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/)
			)
			->count('id');
	}

	/**
	 * Returns whether there are any pending tasks.
	 *
	 * @return bool
	 */
	public function areTasksPending()
	{
		return (bool) craft()->db->createCommand()
			->from('tasks')
			->where(
				array('and', 'lft = 1', 'status = :status'),
				array(':status' => TaskStatus::Pending)
			)
			->count('id');
	}

	/**
	 * Returns whether any tasks that have failed.
	 *
	 * @return bool
	 */
	public function haveTasksFailed()
	{
		return (bool) craft()->db->createCommand()
			->from('tasks')
			->where(array('and', 'level = 0', 'status = :status'), array(':status' => TaskStatus::Error))
			->count('id');
	}

	/**
	 * Returns the total number of active tasks.
	 *
	 * @return bool
	 */
	public function getTotalTasks()
	{
		return craft()->db->createCommand()
			->from('tasks')
			->where(
				array('and', 'lft = 1', 'status != :status'),
				array(':status' => TaskStatus::Error)
			)
			->count('id');
	}

	/**
	 * Returns the next pending task.
	 *
	 * @return TaskModel|null
	 */
	public function getNextPendingTask()
	{
		if (!isset($this->_nextPendingTask))
		{
			$taskRecord = TaskRecord::model()->roots()->ordered()->findByAttributes(array(
				'status' => TaskStatus::Pending
			));

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

	/**
	 * Deletes a task by its ID.
	 *
	 * @param int $taskId
	 * @return bool
	 */
	public function deleteTaskById($taskId)
	{
		$taskRecord = $this->_getTaskRecordById($taskId);
		$success = $taskRecord->deleteNode();
		unset($this->_taskRecordsById[$taskId]);
		return $success;
	}

	/**
	 * Returns a task by its ID.
	 *
	 * @access private
	 * @param int $taskId
	 * @return TaskRecord|null
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
