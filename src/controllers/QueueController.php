<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\App;
use craft\queue\QueueInterface;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The QueueController class is a controller that handles various queue-related operations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class QueueController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['run'];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws ServerErrorHttpException if the `queue` component doesn’t implement [[QueueInterface]]
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Make sure the queue uses our interface
        if (!Craft::$app->getQueue() instanceof QueueInterface) {
            throw new ServerErrorHttpException('The queue class ' . get_class(Craft::$app->getQueue()) . ' doesn’t support web-based runners.');
        }

        return true;
    }

    /**
     * Runs any waiting jobs.
     *
     * @return Response
     */
    public function actionRun(): Response
    {
        // Prep the response
        $response = Craft::$app->getResponse();
        $response->content = '1';

        // Make sure Craft is configured to run queues over the web
        if (!Craft::$app->getConfig()->getGeneral()->runQueueAutomatically) {
            return $response;
        }

        // Make sure the queue isn't already running, and there are waiting jobs
        $queue = Craft::$app->getQueue();
        if ($queue->getHasReservedJobs() || !$queue->getHasWaitingJobs()) {
            return $response;
        }

        // Attempt to close the connection if this is an Ajax request
        if (Craft::$app->getRequest()->getIsAjax()) {
            $response->sendAndClose();
        }

        // Run the queue
        App::maxPowerCaptain();
        $queue->run();

        return $response;
    }

    /**
     * Re-adds a failed job to the queue, and runs any waiting jobs.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionRetry(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requirePermission('accessCp');

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');
        Craft::$app->getQueue()->retry($id);

        return $this->actionRun();
    }

    /**
     * Releases a job from the queue.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionRelease(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requirePermission('accessCp');

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');
        Craft::$app->getQueue()->release($id);

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Returns info about all the jobs in the queue.
     *
     * @return Response
     */
    public function actionGetJobInfo(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('accessCp');

        $limit = Craft::$app->getRequest()->getBodyParam('limit');

        return $this->asJson(Craft::$app->getQueue()->getJobInfo($limit));
    }
}
