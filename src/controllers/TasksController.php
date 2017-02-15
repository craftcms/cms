<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

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
    protected $allowAnonymous = ['run-pending-tasks'];

    // Public Methods
    // =========================================================================

    /**
     * Runs any pending tasks.
     *
     * @return string
     */
    public function actionRunPendingTasks(): string
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

        return '1';
    }

    /**
     * Re-runs a failed task.
     *
     * @return Response
     */
    public function actionRerunTask(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requirePermission('accessCp');

        $taskId = Craft::$app->getRequest()->getRequiredBodyParam('taskId');
        $task = Craft::$app->getTasks()->rerunTaskById($taskId);

        if (!Craft::$app->getTasks()->getIsTaskRunning()) {
            Json::sendJsonHeaders();
            $response = Craft::$app->getResponse();
            $response->content = Json::encode([
                'task' => $task
            ]);
            $response->sendAndClose();

            Craft::$app->getTasks()->runPendingTasks();
        } else {
            return $this->asJson([
                'task' => $task
            ]);
        }

        return $this->asJson([
            'task' => null
        ]);
    }

    /**
     * Deletes a task.
     *
     * @return Response
     */
    public function actionDeleteTask(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requirePermission('accessCp');

        $taskId = Craft::$app->getRequest()->getRequiredBodyParam('taskId');
        Craft::$app->getTasks()->deleteTaskById($taskId);

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Returns info about all the tasks.
     *
     * @return Response
     */
    public function actionGetTaskInfo(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('accessCp');

        return $this->asJson([
            'tasks' => Craft::$app->getTasks()->getTaskInfo(),
        ]);
    }
}
