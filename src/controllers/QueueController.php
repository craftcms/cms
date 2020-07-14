<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\queue\Queue;
use craft\queue\QueueInterface;
use craft\web\Controller;
use yii\base\InvalidArgumentException;
use yii\db\Exception as YiiDbException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The QueueController class is a controller that handles various queue-related operations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class QueueController extends Controller
{
    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['run'];

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
        $this->response->content = '1';

        // Make sure Craft is configured to run queues over the web
        if (!Craft::$app->getConfig()->getGeneral()->runQueueAutomatically) {
            return $this->response;
        }

        // Make sure the queue isn't already running, and there are waiting jobs
        $queue = Craft::$app->getQueue();
        if ($queue->getHasReservedJobs() || !$queue->getHasWaitingJobs()) {
            return $this->response;
        }

        // Attempt to close the connection if this is an Ajax request
        if ($this->request->getIsAjax()) {
            $this->response->sendAndClose();
        }

        // Run the queue
        App::maxPowerCaptain();
        $queue->run();

        return $this->response;
    }

    /**
     * Re-adds a failed job to the queue, and runs any waiting jobs.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 3.1.21
     */
    public function actionRetry(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requirePermission('utility:queue-manager');

        $id = $this->request->getRequiredBodyParam('id');
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
        $this->requirePermission('utility:queue-manager');

        $id = $this->request->getRequiredBodyParam('id');
        Craft::$app->getQueue()->release($id);

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Releases ALL jobs
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws YiiDbException
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    public function actionReleaseAll(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requirePermission('utility:queue-manager');

        Craft::$app->getQueue()->releaseAll();

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Retries ALL jobs
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    public function actionRetryAll(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requirePermission('utility:queue-manager');

        Craft::$app->getQueue()->retryAll();

        return $this->actionRun();
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

        $limit = $this->request->getParam('limit');
        $queue = Craft::$app->getQueue();

        return $this->asJson([
            'total' => $queue->getTotalJobs(),
            'jobs' => $queue->getJobInfo($limit),
        ]);
    }

    /**
     * Returns the details for a particular job. This includes the `job` column containing a lot of raw data.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    public function actionGetJobDetails(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('utility:queue-manager');

        $jobId = $this->request->getRequiredParam('id');
        $details = [
            'id' => $jobId,
        ];

        try {
            $details += Craft::$app->getQueue()->getJobDetails($jobId);
        } catch (InvalidArgumentException $e) {
            $details += [
                'description' => Craft::t('app', 'Completed job'),
                'status' => Queue::STATUS_DONE,
            ];
        }

        if (isset($details['job'])) {
            try {
                $details['job'] = Json::encode($details['job'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (InvalidArgumentException $e) {
                // Just leave the message alone
            }
        }

        return $this->asJson($details);
    }
}
