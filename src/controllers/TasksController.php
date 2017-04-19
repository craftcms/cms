<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The TasksController class is a controller that handles various task related operations such as running, checking task
 * status, re-running and deleting tasks.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
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
     * @return Response
     */
    public function actionRunPendingTasks(): Response
    {
        $tasksService = Craft::$app->getTasks();

        // Prep the response
        $response = Craft::$app->getResponse();
        $response->content = '1';

        // Make sure tasks aren't already running
        if ($tasksService->getIsTaskRunning()) {
            return $response;
        }

        // Make sure there are tasks queued up
        if (!$tasksService->areTasksPending()) {
            return $response;
        }

        // Attempt to close the connection if this is an Ajax request
        if (Craft::$app->getRequest()->getIsAjax()) {
            $response->sendAndClose();
        }

        // Start running tasks
        $tasksService->runPendingTasks();

        return $response;
    }

    /**
     * Re-runs a failed task.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionRerunTask(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requirePermission('accessCp');

        $taskId = Craft::$app->getRequest()->getRequiredBodyParam('taskId');

        try {
            Craft::$app->getTasks()->rerunTaskById($taskId);
        } catch (Exception $e) {
            throw new BadRequestHttpException(null, 0, $e);
        }

        // Prep the response
        $response = Craft::$app->getResponse();
        $response->content = '1';

        // Attempt to kickoff task running if they aren't already, and this is an Ajax request
        if (!Craft::$app->getTasks()->getIsTaskRunning() && Craft::$app->getRequest()->getIsAjax()) {
            $response->sendAndClose();
            Craft::$app->getTasks()->runPendingTasks();
        }

        return $response;
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
