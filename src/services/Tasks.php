<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Task;
use craft\app\base\TaskInterface;
use craft\app\db\Query;
use craft\app\errors\MissingComponentException;
use craft\app\events\TaskEvent;
use craft\app\helpers\Component as ComponentHelper;
use craft\app\helpers\Header;
use craft\app\helpers\Json;
use craft\app\helpers\Url;
use craft\app\records\Task as TaskRecord;
use craft\app\tasks\MissingTask;
use yii\base\Component;
use yii\web\Response;

/**
 * Class Tasks service.
 *
 * An instance of the Tasks service is globally accessible in Craft via [[Application::tasks `Craft::$app->getTasks()`]].
 *
 * @property boolean $isTaskRunning Whether there is a task that is currently running
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Tasks extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event TaskEvent The event that is triggered before a task is saved.
     */
    const EVENT_BEFORE_SAVE_TASK = 'beforeSaveTask';

    /**
     * @event TaskEvent The event that is triggered after a task is saved.
     */
    const EVENT_AFTER_SAVE_TASK = 'afterSaveTask';

    /**
     * @event TaskEvent The event that is triggered before a task is deleted.
     */
    const EVENT_BEFORE_DELETE_TASK = 'beforeDeleteTask';

    /**
     * @event TaskEvent The event that is triggered after a task is deleted.
     */
    const EVENT_AFTER_DELETE_TASK = 'afterDeleteTask';

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
    private $_listeningForResponse = false;

    // Public Methods
    // =========================================================================

    /**
     * Saves a new task and queues it up to be run at the earliest opportunity.
     *
     * @param TaskInterface|array|string $task The task, the task’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @throws \Exception
     * @return TaskInterface The task
     */
    public function queueTask($task)
    {
        if (!$task instanceof TaskInterface) {
            $task = $this->createTask($task);
        }

        $this->saveTask($task);

        if (!$this->_listeningForResponse && Craft::$app->getConfig()->get('runTasksAutomatically') && !Craft::$app->getRequest()->getIsConsoleRequest()) {
            Craft::$app->getResponse()->on(Response::EVENT_AFTER_PREPARE,
                [$this, 'handleResponse']);
            $this->_listeningForResponse = true;
        }

        return $task;
    }

    /**
     * Creates a task with a given config.
     *
     * @param mixed $config The task’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @return TaskInterface The task
     */
    public function createTask($config)
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        try {
            return ComponentHelper::createComponent($config, TaskInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();

            return MissingTask::create($config);
        }
    }

    /**
     * Saves a task.
     *
     * @param TaskInterface $task          The task to be saved
     * @param boolean       $runValidation Whether the task should be validated
     *
     * @return boolean Whether the task was saved successfully
     * @throws \Exception
     */
    public function saveTask(TaskInterface $task, $runValidation = true)
    {
        /** @var Task $task */
        if ($runValidation && !$task->validate()) {
            Craft::info('Task not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewTask = $task->getIsNew();

        // Fire a 'beforeSaveTask' event
        $this->trigger(self::EVENT_BEFORE_SAVE_TASK, new TaskEvent([
            'task' => $task,
            'isNew' => $isNewTask,
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$task->beforeSave()) {
                $transaction->rollBack();

                return false;
            }

            if ($task->getIsNew()) {
                $taskRecord = new TaskRecord();
            } else {
                $taskRecord = $this->_getTaskRecordById($task->id);
            }

            $taskRecord->type = $task->getType();
            $taskRecord->status = $task->status;
            $taskRecord->description = $task->description;
            $taskRecord->totalSteps = $task->totalSteps;
            $taskRecord->currentStep = $task->currentStep;
            $taskRecord->settings = $task->getSettings();

            if (!$task->getIsNew()) {
                $taskRecord->save(false);
            } else if (!$task->parentId) {
                $taskRecord->makeRoot(false);
            } else {
                $parentTaskRecord = $this->_getTaskRecordById($task->parentId);
                $taskRecord->appendTo($parentTaskRecord, false);
            }

            if ($task->getIsNew()) {
                $task->id = $taskRecord->id;

                if ($task->parentId) {
                    // We'll be needing this soon
                    $this->_taskRecordsById[$taskRecord->id] = $taskRecord;
                }
            }

            $task->afterSave();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveTask' event
        $this->trigger(self::EVENT_AFTER_SAVE_TASK, new TaskEvent([
            'task' => $task,
            'isNew' => $isNewTask,
        ]));

        return true;
    }

    /**
     * Closes the connection with the client and turns the request into a task runner.
     */
    public function closeAndRun()
    {
        // Make sure nothing has been output to the browser yet
        if (!headers_sent()) {
            // Close the client connection
            $response = Craft::$app->getResponse();
            $response->content = '1';
            $response->sendAndClose();

            // Run any pending tasks
            $this->runPendingTasks();
        }
    }

    /**
     * Re-runs a task by a given ID.
     *
     * @param integer $taskId The task’s ID
     *
     * @return TaskInterface|null The task
     */
    public function rerunTaskById($taskId)
    {
        /** @var Task|null $task */
        $task = $this->getTaskById($taskId);

        if ($task && $task->level == 0) {
            $task->currentStep = null;
            $task->totalSteps = null;
            $task->status = Task::STATUS_PENDING;
            $this->saveTask($task);

            // Delete any of its subtasks
            $taskRecord = $this->_getTaskRecordById($taskId);
            /** @var TaskRecord[] $subtaskRecords */
            $subtaskRecords = $taskRecord->children()->all();

            foreach ($subtaskRecords as $subtaskRecord) {
                $subtaskRecord->deleteWithChildren();
            }

            return $task;
        }

        return null;
    }

    /**
     * Runs any pending tasks.
     */
    public function runPendingTasks()
    {
        // If we're already processing tasks, let's give it a break.
        if ($this->getIsTaskRunning()) {
            Craft::info('Tasks are already running.', __METHOD__);

            return;
        }

        // It's go time.
        Craft::$app->getConfig()->maxPowerCaptain();

        while ($task = $this->getNextPendingTask()) {
            $this->_runningTask = $task;
            $this->runTask($task);
        }

        $this->_runningTask = null;
    }

    /**
     * Runs a given task.
     *
     * @param TaskInterface $task
     *
     * @return boolean
     */
    public function runTask(TaskInterface $task)
    {
        /** @var Task $task */
        $taskRecord = $this->_getTaskRecordById($task->id);
        $error = null;

        if ($task instanceof MissingTask) {
            $error = $task->errorMessage;
        } else {
            try {
                // Figure out how many total steps there are.
                $task->totalSteps = $task->getTotalSteps();
                $task->status = Task::STATUS_RUNNING;

                Craft::info('Starting task '.$taskRecord->type.' that has a total of '.$task->totalSteps.' steps.');

                for ($step = 0; $step < $task->totalSteps; $step++) {
                    // Update the task
                    $task->currentStep = $step + 1;
                    $this->saveTask($task);

                    Craft::info('Starting step '.($step + 1).' of '.$task->totalSteps.' total steps.');

                    // Run it.
                    if (($result = $task->runStep($step)) !== true) {
                        // Did they give us an error to report?
                        if (is_string($result)) {
                            $error = $result;
                        } else {
                            $error = true;
                        }

                        break;
                    }
                }
            } catch (\Exception $e) {
                $error = 'An exception was thrown: '.$e->getMessage();
            }
        }

        if ($task == $this->_nextPendingTask) {
            // Don't run this again
            $this->_nextPendingTask = null;
        }

        if ($error === null) {
            Craft::info('Finished task '.$task->id.' ('.$task->type.').', __METHOD__);

            // We're done with this task, nuke it.
            $taskRecord->deleteWithChildren();

            return true;
        }

        $this->fail($task, $error);

        return false;
    }

    /**
     * Sets a task's status to "error" and logs it.
     *
     * @param TaskInterface $task  The task
     * @param string|null   $error The error message
     *
     * @return void
     */
    public function fail(TaskInterface $task, $error = null)
    {
        /** @var Task $task */
        $task->status = Task::STATUS_ERROR;
        $this->saveTask($task);

        // Log it
        $logMessage = 'Encountered an error running task '.$task->id.' ('.$task->type.')';

        if ($task->currentStep) {
            $logMessage .= ', step '.$task->currentStep;

            if ($task->totalSteps) {
                $logMessage .= ' of '.$task->totalSteps;
            }
        }

        if ($error && is_string($error)) {
            $logMessage .= ': '.$error;
        } else {
            $logMessage .= '.';
        }

        Craft::error($logMessage, __METHOD__);
    }

    /**
     * Returns a task by its ID.
     *
     * @param integer $taskId The task’s ID
     *
     * @return TaskInterface|null The task, or null if it doesn’t exist
     */
    public function getTaskById($taskId)
    {
        $result = (new Query())
            ->select('*')
            ->from('{{%tasks}}')
            ->where('id = :id', [':id' => $taskId])
            ->one();

        if ($result !== false) {
            return $this->createTask($result);
        }

        return null;
    }

    /**
     * Returns all the tasks.
     *
     * @return TaskInterface[] All the tasks
     */
    public function getAllTasks()
    {
        $tasks = (new Query())
            ->select('*')
            ->from('{{%tasks}}')
            ->orderBy('root asc, lft asc')
            ->all();

        foreach ($tasks as $key => $value) {
            $tasks[$key] = $this->createTask($value);
        }

        return $tasks;
    }

    /**
     * Returns the currently running task.
     *
     * @return TaskInterface|null The currently running task, or null if there isn’t one
     */
    public function getRunningTask()
    {
        if ($this->_runningTask === null) {
            $result = (new Query())
                ->select('*')
                ->from('{{%tasks}}')
                ->where(
                    [
                        'and',
                        'lft = 1',
                        'status = :status'
                        /*, 'dateUpdated >= :aMinuteAgo'*/
                    ],
                    [
                        ':status' => Task::STATUS_RUNNING
                        /*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/
                    ]
                )
                ->one();

            if ($result !== false) {
                $this->_runningTask = $this->createTask($result);
            } else {
                $this->_runningTask = false;
            }
        }

        if ($this->_runningTask) {
            return $this->_runningTask;
        }

        return null;
    }

    /**
     * Returns whether there is a task that is currently running.
     *
     * @return boolean Whether there is a task that is currently running
     */
    public function getIsTaskRunning()
    {
        // Remember that a root task could appear to be stagnant if it has sub-tasks.
        return (new Query())
            ->from('{{%tasks}}')
            ->where(
                ['and', 'status = :status'/*, 'dateUpdated >= :aMinuteAgo'*/],
                [
                    ':status' => Task::STATUS_RUNNING
                    /*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/
                ]
            )
            ->exists();
    }

    /**
     * Returns whether there are any pending tasks, optionally by a given type.
     *
     * @param string|null $type The task type to check for, if any
     *
     * @return boolean Whether there are any pending tasks
     */
    public function areTasksPending($type = null)
    {
        $conditions = ['and', 'lft = 1', 'status = :status'];
        $params = [':status' => Task::STATUS_PENDING];

        if ($type) {
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
     *
     * @return TaskInterface[] The pending tasks
     */
    public function getPendingTasks($type = null, $limit = null)
    {
        $conditions = ['and', 'lft = 1', 'status = :status'];
        $params = [':status' => Task::STATUS_PENDING];

        if ($type) {
            $conditions[] = 'type = :type';
            $params[':type'] = $type;
        }

        $query = (new Query())
            ->from('{{%tasks}}')
            ->where($conditions, $params);

        if ($limit) {
            $query->limit($limit);
        }

        $tasks = $query->all();

        foreach ($tasks as $key => $value) {
            $tasks[$key] = $this->createTask($value);
        }

        return $tasks;
    }

    /**
     * Returns whether any tasks that have failed.
     *
     * @return boolean Whether any tasks have failed
     */
    public function getHaveTasksFailed()
    {
        return (new Query())
            ->from('{{%tasks}}')
            ->where(['and', 'level = 0', 'status = :status'],
                [':status' => Task::STATUS_ERROR])
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
     *
     * @return TaskInterface|null The next pending task, if any
     */
    public function getNextPendingTask($type = null)
    {
        // If a type was passed, we don't need to actually save it, as it's probably not an actual task-running request.
        if ($type) {
            $pendingTasks = $this->getPendingTasks($type, 1);

            if ($pendingTasks) {
                return $pendingTasks[0];
            }
        } else {
            if ($this->_nextPendingTask === null) {
                $taskRecord = TaskRecord::find()
                    ->where(['status' => Task::STATUS_PENDING])
                    ->orderBy('dateCreated')
                    ->roots()
                    ->one();

                if ($taskRecord) {
                    /** @var TaskRecord $taskRecord */
                    $this->_taskRecordsById[$taskRecord->id] = $taskRecord;
                    $this->_nextPendingTask = $this->createTask($taskRecord);
                } else {
                    $this->_nextPendingTask = false;
                }
            }

            if ($this->_nextPendingTask !== false) {
                return $this->_nextPendingTask;
            }

            return null;
        }

        return null;
    }

    /**
     * Deletes a task by its ID.
     *
     * @param integer $taskId The task’s ID
     *
     * @return boolean Whether the task was deleted successfully
     */
    public function deleteTaskById($taskId)
    {
        $task = $this->getTaskById($taskId);

        if (!$task) {
            return false;
        }

        return $this->deleteTask($task);
    }

    /**
     * Deletes a task.
     *
     * @param TaskInterface $task The task
     *
     * @return boolean Whether the task was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteTask(TaskInterface $task)
    {
        /** @var Task $task */
        $taskRecord = $this->_getTaskRecordById($task->id);

        if ($taskRecord === null) {
            // Fake it
            return true;
        }

        // Fire a 'beforeDeleteTask' event
        $this->trigger(self::EVENT_BEFORE_DELETE_TASK, new TaskEvent([
            'task' => $task,
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$task->beforeDelete()) {
                $transaction->rollBack();

                return false;
            }

            $taskRecord->deleteWithChildren();
            unset($this->_taskRecordsById[$task->id]);

            $task->afterDelete();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteTask' event
        $this->trigger(self::EVENT_AFTER_DELETE_TASK, new TaskEvent([
            'task' => $task,
        ]));

        return true;
    }

    /**
     * Figure out how to initiate a new task runner.
     */
    public function handleResponse()
    {
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();

        $response->off(Response::EVENT_AFTER_PREPARE, [
            $this,
            'handleResponse'
        ]);

        // Ignore if tasks are already running
        if ($this->getIsTaskRunning()) {
            return;
        }

        // Make sure nothing has been output to the browser yet, and there's no pending response body
        if (!headers_sent() && !ob_get_length() && $response->content === null) {
            $this->closeAndRun();
        }
        // Is this a non-AJAX site request and are we responding with HTML or XHTML?
        // (CP requests don't need to be told to run pending tasks)
        else if (
            $request->getIsSiteRequest() &&
            !$request->getIsAjax() &&
            in_array(Header::getMimeType(), [
                'text/html',
                'application/xhtml+xml'
            ])
        ) {
            // Just output JS that tells the browser to fire an Ajax request to kick off task running
            $url = Json::encode(Url::getActionUrl('tasks/run-pending-tasks'));

            // Ajax request code adapted from http://www.quirksmode.org/js/xmlhttp.html - thanks ppk!
            $js = <<<EOT
<script type="text/javascript">
/*<![CDATA[*/
(function(){
	var XMLHttpFactories = [
		function () {return new XMLHttpRequest()},
		function () {return new ActiveXObject("Msxml2.XMLHTTP")},
		function () {return new ActiveXObject("Msxml3.XMLHTTP")},
		function () {return new ActiveXObject("Microsoft.XMLHTTP")}
	];
	var req = false;
	for (var i = 0; i < XMLHttpFactories.length; i++) {
		try {
			req = XMLHttpFactories[i]();
		}
		catch (e) {
			continue;
		}
		break;
	}
	if (!req) return;
	req.open('GET', $url, true);
	if (req.readyState == 4) return;
	req.send();
})();
/*]]>*/
</script>
EOT;

            if ($response->content === null) {
                $response->content = $js;
            } else {
                $response->content .= $js;
            }
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a TaskRecord by its ID.
     *
     * @param integer $taskId The task’s ID
     *
     * @return TaskRecord|null The TaskRecord, or null if it doesn’t exist
     */
    private function _getTaskRecordById($taskId)
    {
        if (!isset($this->_taskRecordsById[$taskId])) {
            $this->_taskRecordsById[$taskId] = TaskRecord::findOne($taskId);

            if ($this->_taskRecordsById[$taskId] === null) {
                $this->_taskRecordsById[$taskId] = false;
            }
        }

        if ($this->_taskRecordsById[$taskId] !== false) {
            return $this->_taskRecordsById[$taskId];
        }

        return null;
    }
}
