<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Composer\IO\BufferIO;
use Craft;
use craft\helpers\Json;
use craft\web\assets\updater\UpdaterAsset;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * BaseUpdaterController provides the base class for Craft/plugin installation/updating/removal.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class BaseUpdaterController extends Controller
{
    // Constants
    // =========================================================================

    const ACTION_RECHECK_COMPOSER = 'recheck-composer';
    const ACTION_COMPOSER_INSTALL = 'composer-install';
    const ACTION_COMPOSER_REMOVE = 'composer-remove';
    const ACTION_COMPOSER_OPTIMIZE = 'composer-optimize';
    const ACTION_FINISH = 'finish';

    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    /**
     * @var array The data associated with the current update
     */
    protected $data = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws NotFoundHttpException if it's not a CP request
     * @throws BadRequestHttpException if there's invalid data in the request
     */
    public function beforeAction($action)
    {
        // This controller is only available to the CP
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            throw new NotFoundHttpException();
        }

        $this->requirePostRequest();

        if (!parent::beforeAction($action)) {
            return false;
        }

        if ($action->id !== 'index') {
            if (($data = Craft::$app->getRequest()->getValidatedBodyParam('data')) === null) {
                throw new BadRequestHttpException();
            }

            $this->data = Json::decode($data);
        }

        return true;
    }

    /**
     * Kicks off the update.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionIndex(): Response
    {
        // Load the updater JS
        $view = $this->getView();
        $view->registerAssetBundle(UpdaterAsset::class);

        $this->data = $this->initialData();
        $state = $this->initialState();
        $state['data'] = $this->_hashedData();
        $idJs = Json::encode($this->id);
        $stateJs = Json::encode($state);
        $this->getView()->registerJs("Craft.updater = (new Craft.Updater({$idJs})).setState($stateJs);");

        return $this->renderTemplate('_special/updater', [
            'title' => $this->pageTitle(),
        ]);
    }

    /**
     * Rechecks for composer.json, if it couldn't be found in the initial state.
     *
     * @return Response
     */
    public function actionRecheckComposer(): Response
    {
        return $this->send($this->initialState());
    }

    /**
     * Installs Composer dependencies.
     *
     * @return Response
     */
    public function actionComposerInstall(): Response
    {
        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->install($this->data['requirements'], $io);
        } catch (\Throwable $e) {
            Craft::error('Error updating Composer requirements: '.$e->getMessage()."\nOutput: ".$io->getOutput(), __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            return $this->sendComposerError(Craft::t('app', 'Composer was unable to install the updates.'), $e, $io);
        }

        return $this->sendNextAction(self::ACTION_COMPOSER_OPTIMIZE);
    }

    /**
     * Removes Composer dependencies.
     *
     * @return Response
     */
    public function actionComposerRemove(): Response
    {
        $packages = [$this->data['packageName']];
        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->uninstall($packages, $io);
            $this->data['removed'] = true;
        } catch (\Throwable $e) {
            Craft::error('Error updating Composer requirements: '.$e->getMessage()."\nOutput: ".$io->getOutput(), __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            return $this->sendComposerError(Craft::t('app', 'Composer was unable to remove the plugin.'), $e, $io);
        }

        return $this->sendNextAction(self::ACTION_COMPOSER_OPTIMIZE);
    }

    /**
     * Optimizes the Composer autoloader.
     *
     * @return Response
     */
    public function actionComposerOptimize(): Response
    {
        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->optimize($io);
        } catch (\Throwable $e) {
            Craft::error('Error optimizing the Composer autoloader: '.$e->getMessage()."\nOutput: ".$io->getOutput(), __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            $continueOption = $this->postComposerOptimizeState();
            $continueOption['label'] = Craft::t('app', 'Continue');
            return $this->send([
                'error' => Craft::t('app', 'Composer was unable to optimize the autoloader.'),
                'errorDetails' => $this->_composerErrorDetails($e, $io),
                'options' => [
                    $this->actionOption(Craft::t('app', 'Try again'), self::ACTION_COMPOSER_OPTIMIZE),
                    $continueOption,
                ]
            ]);
        }

        return $this->send($this->postComposerOptimizeState());
    }

    /**
     * Finishes the update process.
     *
     * @return Response
     */
    public function actionFinish(): Response
    {
        // Disable maintenance mode
        Craft::$app->disableMaintenanceMode();

        return $this->send([
            'finished' => true,
            'returnUrl' => $this->returnUrl(),
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the page title
     *
     * @return string
     */
    abstract protected function pageTitle(): string;

    /**
     * Returns the initial data.
     *
     * @return array
     */
    abstract protected function initialData(): array;

    /**
     * Returns the initial state for the updater JS.
     *
     * @return array
     */
    abstract protected function initialState(): array;

    /**
     * Returns the state data for after [[actionComposerOptimize()]] is done.
     *
     * @return array
     */
    abstract protected function postComposerOptimizeState(): array;

    /**
     * Returns the return URL that should be passed with a finished state.
     *
     * @return string
     */
    abstract protected function returnUrl(): string;

    /**
     * Ensures that composer.json can be found.
     *
     * @return bool Whether composer.json can be found
     */
    protected function ensureComposerJson()
    {
        try {
            Craft::$app->getComposer()->getJsonPath();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns the initial state if composer.json couldn't be found.
     *
     * @return array
     * @see ensureComposerJson()
     */
    protected function noComposerJsonState(): array
    {
        return [
            'error' => Craft::t('app', 'Your composer.json file could not be located. Try setting the CRAFT_COMPOSER_PATH constant in index.php to its location on the server.'),
            'errorDetails' => 'define(\'CRAFT_COMPOSER_PATH\', \'path/to/composer.json\');',
            'options' => [
                $this->actionOption(Craft::t('app', 'Try again'), self::ACTION_RECHECK_COMPOSER, ['submit' => true]),
            ]
        ];
    }

    /**
     * Sends a state response.
     *
     * @param array $state
     *
     * @return Response
     */
    protected function send(array $state = []): Response
    {
        // Encode and hash the data
        $state['data'] = $this->_hashedData();

        return $this->asJson($state);
    }

    /**
     * Sends a "next action" state response.
     *
     * @param string $nextAction The next action that should be run
     * @param array  $state
     *
     * @return Response
     */
    protected function sendNextAction(string $nextAction, array $state = []): Response
    {
        $state = $this->actionState($nextAction, $state);
        return $this->send($state);
    }

    /**
     * Sends a "finished" state response.
     *
     * @param array $state
     *
     * @return Response
     */
    protected function sendFinished(array $state = []): Response
    {
        $state = $this->finishedState($state);
        return $this->send($state);
    }

    /**
     * Sends an "error" state response for a Composer error
     *
     * @param string     $error The status message to show
     * @param \Throwable $e     The exception that was thrown
     * @param BufferIO   $io    The IO object that Composer was instantiated with
     * @param array      $state
     *
     * @return Response
     */
    protected function sendComposerError(string $error, \Throwable $e, BufferIO $io, array $state = []): Response
    {
        $state['error'] = $error;
        $state['errorDetails'] = $this->_composerErrorDetails($e, $io);

        $state['options'] = [
            [
                'label' => Craft::t('app', 'Send for help'),
                'submit' => true,
                'email' => 'support@craftcms.com',
                'subject' => 'Composer error',
            ]
        ];

        return $this->send($state);
    }

    /**
     * Returns an option definition that kicks off a new action.
     *
     * @param string $label
     * @param string $action
     * @param array  $state
     *
     * @return array
     */
    protected function actionOption(string $label, string $action, array $state = []): array
    {
        $state['label'] = $label;
        return $this->actionState($action, $state);
    }

    /**
     * Sets the state info for the given next action.
     *
     * @param string $nextAction
     * @param array  $state
     *
     * @return array
     */
    protected function actionState(string $nextAction, array $state = []): array
    {
        $state['nextAction'] = $nextAction;

        if (!isset($state['status'])) {
            $state['status'] = $this->actionStatus($nextAction);
        }

        return $state;
    }

    /**
     * Returns the status message for the given action.
     *
     * @param string $action
     *
     * @return string
     * @throws Exception if $action isn't valid
     */
    protected function actionStatus(string $action): string
    {
        switch ($action) {
            case self::ACTION_RECHECK_COMPOSER:
                return Craft::t('app', 'Checking…');
            case self::ACTION_COMPOSER_INSTALL:
                return Craft::t('app', 'Running {command} (this may take a minute)…', [
                    'command' => '`composer install`'
                ]);
            case self::ACTION_COMPOSER_REMOVE:
                return Craft::t('app', 'Running {command} (this may take a minute)…', [
                    'command' => '`composer remove`'
                ]);
            case self::ACTION_COMPOSER_OPTIMIZE:
                return Craft::t('app', 'Optimizing…');
            case self::ACTION_FINISH:
                return Craft::t('app', 'Finishing up…');
            default:
                throw new Exception('Invalid action: '.$action);
        }
    }

    /**
     * Sets the state info for when the job is done.
     *
     * @param array $state
     *
     * @return array
     */
    protected function finishedState(array $state = []): array
    {
        if (!isset($state['status']) && !isset($state['error'])) {
            $state['status'] = Craft::t('app', 'All done!');
        }

        $state['finished'] = true;
        $state['returnUrl'] = $this->returnUrl();

        return $state;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the hashed data for JS.
     *
     * @return string
     */
    private function _hashedData(): string
    {
        return Craft::$app->getSecurity()->hashData(Json::encode($this->data));
    }

    /**
     * Returns the error details for a Composer error.
     *
     * @param \Throwable $e  The exception that was thrown
     * @param BufferIO   $io The IO object that Composer was instantiated with
     *
     * @return string
     */
    private function _composerErrorDetails(\Throwable $e, BufferIO $io): string
    {
        return Craft::t('app', 'Error:').' '.$e->getMessage()."\n\n".
            Craft::t('app', 'Output:').' '.strip_tags($io->getOutput());
    }
}
