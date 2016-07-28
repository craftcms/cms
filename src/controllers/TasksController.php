<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\helpers\Json;
use craft\app\web\Controller;
use yii\web\Response;

/**
 * The TasksController class is a controller that handles various task related operations such as running, checking task
 * status, re-running and deleting tasks.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @return void
     */
    public function actionRunPendingTasks()
    {
        $tasksService = Craft::$app->getTasks();

        // Make sure tasks aren't already running
        if (!$tasksService->getIsTaskRunning()) {
            $task = $tasksService->getNextPendingTask();

            if ($task) {
                // Attempt to close the connection if this is an Ajax request
                if (Craft::$app->getRequest()->getIsAjax()) {
                    $response = Craft::$app->getResponse();
                    $response->content = '1';
                    $response->sendAndClose();
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
     * @return Response|null
     */
    public function actionGetRunningTaskInfo()
    {
        $this->requireAjaxRequest();
        $this->requirePermission('accessCp');

        $tasksService = Craft::$app->getTasks();

        if ($task = $tasksService->getRunningTask()) {
            return $this->asJson($task);
        }

        // No running tasks left? Check for a failed one
        if ($tasksService->haveTasksFailed()) {
            return $this->asJson(['status' => 'error']);
        }

        // Any pending tasks?
        if ($task = $tasksService->getNextPendingTask()) {
            return $this->asJson($task);
        }

        Craft::$app->end();

        return null;
    }

    /**
     * Re-runs a failed task.
     *
     * @return Response|null
     */
    public function actionRerunTask()
    {
        $this->requireAjaxRequest();
        $this->requirePostRequest();
        $this->requirePermission('accessCp');

        $taskId = Craft::$app->getRequest()->getRequiredBodyParam('taskId');
        $task = Craft::$app->getTasks()->rerunTaskById($taskId);

        if (!Craft::$app->getTasks()->getIsTaskRunning()) {
            Json::sendJsonHeaders();
            $response = Craft::$app->getResponse();
            $response->content = Json::encode($task);
            $response->sendAndClose();

            Craft::$app->getTasks()->runPendingTasks();
        } else {
            return $this->asJson($task);
        }

        Craft::$app->end();

        return null;
    }

    /**
     * Deletes a task.
     *
     * @return void
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
     * @return Response
     */
    public function actionGetTaskInfo()
    {
        $this->requireAjaxRequest();
        $this->requirePermission('accessCp');

        return $this->asJson(Craft::$app->getTasks()->getAllTasks());
    }
}
