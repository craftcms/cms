<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Task;
use craft\base\TaskInterface;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\events\TaskEvent;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Header;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\records\Task as TaskRecord;
use craft\tasks\MissingTask;
use yii\base\Component;
use yii\base\Exception;
use yii\web\Response;

/**
 * Class Tasks service.
 *
 * An instance of the Tasks service is globally accessible in Craft via [[Application::tasks `Craft::$app->getTasks()`]].
 *
 * @property bool $isTaskRunning Whether there is a task that is currently running
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
     * @param TaskInterface|array|string $task         The task, the task’s class name, or its config, with a `type` value and optionally a `settings` value
     * @param bool                       $maybeAutoRun Whether this request should maybe be turned into a task runner
     *
     * @throws \Exception
     * @return TaskInterface The task
     */
    public function queueTask($task, $maybeAutoRun = true): TaskInterface
    {
        if (!$task instanceof TaskInterface) {
            $task = $this->createTask($task);
        }

        $this->saveTask($task);

        if (
            $maybeAutoRun === true &&
            $this->_listeningForResponse === false &&
            Craft::$app->getConfig()->get('runTasksAutomatically') &&
            !Craft::$app->getRequest()->getIsConsoleRequest()
        ) {
            Craft::$app->getResponse()->on(Response::EVENT_AFTER_PREPARE, [$this, 'handleResponse']);
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
    public function createTask($config): TaskInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        try {
            /** @var Task $task */
            $task = ComponentHelper::createComponent($config, TaskInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $task = new MissingTask($config);
        }

        return $task;
    }

    /**
     * Saves a task.
     *
     * @param TaskInterface $task          The task to be saved
     * @param bool          $runValidation Whether the task should be validated
     *
     * @return bool Whether the task was saved successfully
     * @throws \Exception
     */
    public function saveTask(TaskInterface $task, bool $runValidation = true): bool
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
            if (!$task->beforeSave($isNewTask)) {
                $transaction->rollBack();

                return false;
            }

            if ($task->getIsNew()) {
                $taskRecord = new TaskRecord();
            } else {
                $taskRecord = $this->_getTaskRecordById($task->id);
            }

            $taskRecord->type = get_class($task);
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

                if ($parentTaskRecord === null) {
                    throw new Exception('There was a problem gettin the parent task record.');
                }

                $taskRecord->appendTo($parentTaskRecord, false);
            }

            if ($task->getIsNew()) {
                $task->id = $taskRecord->id;

                if ($task->parentId) {
                    // We'll be needing this soon
                    $this->_taskRecordsById[$taskRecord->id] = $taskRecord;
                }
            }

            $task->afterSave($isNewTask);

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
     * @param int $taskId The task’s ID
     *
     * @return TaskInterface|null The task
     */
    public function rerunTaskById(int $taskId)
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
     * @return bool
     */
    public function runTask(TaskInterface $task): bool
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

                Craft::info('Starting task '.$taskRecord->type.' that has a total of '.$task->totalSteps.' steps.', __METHOD__);

                for ($step = 0; $step < $task->totalSteps; $step++) {
                    // Update the task
                    $task->currentStep = $step + 1;
                    $this->saveTask($task);

                    Craft::info('Starting step '.($step + 1).' of '.$task->totalSteps.' total steps.', __METHOD__);

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
            Craft::info('Finished task '.$task->id.' ('.get_class($task).').', __METHOD__);

            // We're done with this task, nuke it.
            $taskRecord->deleteWithChildren();

            return true;
        }

        $this->fail($task, is_string($error) ? $error : null);

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
    public function fail(TaskInterface $task, string $error = null)
    {
        /** @var Task $task */
        $task->status = Task::STATUS_ERROR;
        $this->saveTask($task);

        // Log it
        $logMessage = 'Encountered an error running task '.$task->id.' ('.get_class($task).')';

        if ($task->currentStep) {
            $logMessage .= ', step '.$task->currentStep;

            if ($task->totalSteps) {
                $logMessage .= ' of '.$task->totalSteps;
            }
        }

        if ($error !== null) {
            $logMessage .= ': '.$error;
        } else {
            $logMessage .= '.';
        }

        Craft::error($logMessage, __METHOD__);
    }

    /**
     * Returns a task by its ID.
     *
     * @param int $taskId The task’s ID
     *
     * @return TaskInterface|null The task, or null if it doesn’t exist
     */
    public function getTaskById(int $taskId)
    {
        $result = $this->_createTaskQuery()
            ->where(['id' => $taskId])
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
    public function getAllTasks(): array
    {
        $tasks = $this->_createTaskQuery()
            ->all();

        foreach ($tasks as $key => $value) {
            $tasks[$key] = $this->createTask($value);
        }

        return $tasks;
    }

    /**
     * Returns information about all the tasks.
     *
     * @return array
     */
    public function getTaskInfo(): array
    {
        $taskInfo = [];

        foreach ($this->getAllTasks() as $task) {
            /** @var Task $task */
            $info = $task->toArray(['id', 'level', 'description', 'status', 'progress']);
            // Include how many seconds it's been since it was updated
            $info['age'] = time() - $task->dateUpdated->getTimestamp();
            $taskInfo[] = $info;
        }

        return $taskInfo;
    }

    /**
     * Returns the currently running task.
     *
     * @return TaskInterface|null The currently running task, or null if there isn’t one
     */
    public function getRunningTask()
    {
        if ($this->_runningTask === null) {
            $result = $this->_createTaskQuery()
                ->where([
                    'lft' => '1',
                    'status' => Task::STATUS_RUNNING,
                    /* ['>=', 'dateUpdated' >= DateTimeHelper::formatTimeForDb('-1 minute')], */
                ])
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
     * @return bool Whether there is a task that is currently running
     */
    public function getIsTaskRunning(): bool
    {
        // Remember that a root task could appear to be stagnant if it has sub-tasks.
        return $this->_createTaskQuery()
            ->where([
                'and',
                ['status' => Task::STATUS_RUNNING],
                /* ['>=', 'dateUpdated', DateTimeHelper::formatTimeForDb('-1 minute')], */
            ])
            ->exists();
    }

    /**
     * Returns whether there are any pending tasks, optionally by a given type.
     *
     * @param string|null $type The task type to check for, if any
     *
     * @return bool Whether there are any pending tasks
     */
    public function areTasksPending(string $type = null): bool
    {
        $query = $this->_createTaskQuery()
            ->where([
                'lft' => '1',
                'status' => Task::STATUS_PENDING
            ]);

        if ($type !== null) {
            $query->andWhere(['type' => $type]);
        }

        return $query->exists();
    }

    /**
     * Returns any pending tasks, optionally by a given type.
     *
     * @param string|null $type  The task type to check for, if any
     * @param int|null    $limit The maximum number of tasks to return
     *
     * @return TaskInterface[] The pending tasks
     */
    public function getPendingTasks(string $type = null, int $limit = null): array
    {
        $query = $this->_createTaskQuery()
            ->where(['lft' => 1, 'status' => Task::STATUS_PENDING]);

        if ($type !== null) {
            $query->andWhere(['type' => $type]);
        }

        if ($limit !== null) {
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
     * @return bool Whether any tasks have failed
     */
    public function getHaveTasksFailed(): bool
    {
        return $this->_createTaskQuery()
            ->where(['level' => 0, 'status' => Task::STATUS_ERROR])
            ->exists();
    }

    /**
     * Returns the total number of active tasks.
     *
     * @return int The total number of active tasks
     */
    public function getTotalTasks(): int
    {
        return $this->_createTaskQuery()
            ->where([
                'and',
                ['lft' => '1'],
                ['not', ['status' => Task::STATUS_ERROR]]
            ])
            ->count('[[id]]');
    }

    /**
     * Returns the next pending task.
     *
     * @param string|null $type The type of task to check for, if any
     *
     * @return TaskInterface|null The next pending task, if any
     */
    public function getNextPendingTask(string $type = null)
    {
        // If a type was passed, we don't need to actually save it, as it's probably not an actual task-running request.
        if ($type !== null) {
            $pendingTasks = $this->getPendingTasks($type, 1);

            if (!empty($pendingTasks)) {
                return $pendingTasks[0];
            }
        } else {
            if ($this->_nextPendingTask === null) {
                $taskRecord = TaskRecord::find()
                    ->where(['status' => Task::STATUS_PENDING])
                    ->orderBy(['dateCreated' => SORT_ASC])
                    ->roots()
                    ->one();

                if ($taskRecord) {
                    /** @var TaskRecord $taskRecord */
                    $this->_taskRecordsById[$taskRecord->id] = $taskRecord;
                    $this->_nextPendingTask = $this->createTask($taskRecord->toArray([
                        'id',
                        'dateCreated',
                        'dateUpdated',
                        'level',
                        'description',
                        'totalSteps',
                        'currentStep',
                        'status',
                        'type',
                        'settings',
                    ]));
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
     * @param int $taskId The task’s ID
     *
     * @return bool Whether the task was deleted successfully
     */
    public function deleteTaskById(int $taskId): bool
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
     * @return bool Whether the task was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteTask(TaskInterface $task): bool
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

        $response->off(Response::EVENT_AFTER_PREPARE, [$this, 'handleResponse']);

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
            in_array(Header::getMimeType(), ['text/html', 'application/xhtml+xml'], true)
        ) {
            // Just output JS that tells the browser to fire an Ajax request to kick off task running
            $url = Json::encode(UrlHelper::actionUrl('tasks/run-pending-tasks'));

            // Ajax request code adapted from http://www.quirksmode.org/js/xmlhttp.html - thanks ppk!
            $js = <<<EOD
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
EOD;

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
     * Returns a Query object prepped for retrieving tasks.
     *
     * @return Query
     */
    private function _createTaskQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'dateCreated',
                'dateUpdated',
                'level',
                'description',
                'totalSteps',
                'currentStep',
                'status',
                'type',
                'settings',
            ])
            ->from(['{{%tasks}}'])
            ->orderBy(['root' => SORT_ASC, 'lft' => SORT_ASC]);
    }

    /**
     * Returns a TaskRecord by its ID.
     *
     * @param int $taskId The task’s ID
     *
     * @return TaskRecord|null The TaskRecord, or null if it doesn’t exist
     */
    private function _getTaskRecordById(int $taskId)
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
