<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use craft\console\Controller;
use yii\console\ExitCode;
use Craft;

/**
 * Console based Queue actions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class QueueController extends Controller
{
    // Public Properties
    // =========================================================================

    /**
     * @var bool Whether all actions should be executed on a queue action
     */
    public $all = false;

    // Public Methods
    // =========================================================================

    /**
     * TODO: What to return in beforeAction on fail?
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Make sure the queue uses our interface
        if (!Craft::$app->getQueue() instanceof QueueInterface) {
            $this->stderr('The queue class ' . get_class(Craft::$app->getQueue()) . ' doesn’t support console-based runners.');
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function actionRun()
    {
        $this->stdout('Running queue'.PHP_EOL);

        Craft::$app->getQueue()->run();

        $this->stdout('Queue successfully executed'.PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * @param null $id
     * @return int
     */
    public function actionRetry($id = null)
    {
        if ($this->force = true) {
            $this->stdout('Retrying all jobs'. PHP_EOL);

            Craft::$app->getQueue()->retryAll();
            Craft::$app->getQueue()->run();

            $this->stdout('All jobs retried'. PHP_EOL);

            return ExitCode::OK;
        }

        // Fail-safe
        if (!$id) {
            $id = $this->prompt('You have not entered an ID. Enter one now.'.PHP_EOL);
            if (!$id) {
                $this->stderr('Invalid id'.PHP_EOL);
                return ExitCode::OK;
            }
        }

        // Retry with specific id
        $this->stdout("Retrying job with id: $id". PHP_EOL);

        Craft::$app->getQueue()->retry($id);
        Craft::$app->getQueue()->run();

        $this->stdout("Job with id: $id retried". PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * @param null $id
     * @return int
     * @throws \yii\db\Exception
     */
    public function actionRelease($id = null)
    {
        if ($this->force = true) {
            $this->stdout('Releasing all jobs'. PHP_EOL);

            \Craft::$app->getQueue()->releaseAll();
            \Craft::$app->getQueue()->run();

            $this->stdout('All jobs released'. PHP_EOL);

            return ExitCode::OK;
        }

        // Fail-safe
        if (!$id) {
            $id = $this->prompt('You have not entered an ID. Enter one now.'.PHP_EOL);
            if (!$id) {
                $this->stderr('Invalid id'.PHP_EOL);
                return ExitCode::OK;
            }
        }

        // Retry with specific id
        $this->stdout("Releasing job with id: $id". PHP_EOL);

        Craft::$app->getQueue()->release($id);
        Craft::$app->getQueue()->run();

        $this->stdout("Job with id: $id released". PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        $options[] = 'all';

        return $options;
    }
}
