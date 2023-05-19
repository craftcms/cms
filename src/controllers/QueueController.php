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
use craft\queue\QueueInterface;
use craft\web\Controller;
use yii\base\InvalidArgumentException;
use yii\db\Exception as YiiDbException;
use yii\queue\Queue;
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
    protected array|bool|int $allowAnonymous = ['run'];

    private QueueInterface $queue;

    /**
     * @inheritdoc
     * @throws ServerErrorHttpException
     */
    public function init(): void
    {
        parent::init();

        $queue = Craft::$app->getQueue();
        if (!$queue instanceof QueueInterface) {
            throw new ServerErrorHttpException(sprintf('The queue class %s doesn’t support web-based runners.', get_class($queue)));
        }
        $this->queue = $queue;
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
        if ($this->queue->getHasReservedJobs() || !$this->queue->getHasWaitingJobs()) {
            return $this->response;
        }

        // Attempt to close the connection if this is an Ajax request
        if ($this->request->getIsAjax()) {
            $this->response->sendAndClose();
        }

        // Run the queue
        App::maxPowerCaptain();
        $this->queue->run();

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
        $this->queue->retry($id);

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
        $this->queue->release($id);

        return $this->asSuccess();
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

        $this->queue->releaseAll();

        return $this->asSuccess();
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

        $this->queue->retryAll();

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

        return $this->asJson([
            'total' => $this->queue->getTotalJobs(),
            'jobs' => $this->queue->getJobInfo($limit),
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
            $details += $this->queue->getJobDetails($jobId);
        } catch (InvalidArgumentException) {
            $details += [
                'description' => Craft::t('app', 'Completed job'),
                'status' => Queue::STATUS_DONE,
            ];
        }

        if (isset($details['job'])) {
            try {
                $details['job'] = Json::encode($details['job'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (InvalidArgumentException) {
                // Just leave the message alone
            }
        }

        return $this->asJson($details);
    }
}
