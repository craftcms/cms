<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\controllers\pluginstore;

use Composer\IO\BufferIO;
use Craft;
use craft\errors\MigrateException;
use craft\errors\MigrationException;
use craft\helpers\Json;
use craft\web\assets\updater\UpdaterAsset;
use craft\web\Controller;
use yii\base\Exception as YiiException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * UpdaterController handles various update tasks in coordination with the Craft.Updater JS class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class InstallController extends Controller
{
    // Constants
    // =========================================================================

    const ACTION_RECHECK_COMPOSER = 'recheck-composer';
    const ACTION_COMPOSER_INSTALL = 'composer-install';
    const ACTION_OPTIMIZE = 'optimize';
    const ACTION_INSTALL = 'install';

    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    /**
     * @var array The data associated with the current update
     */
    private $_data = [];

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

        if (!parent::beforeAction($action)) {
            return false;
        }

        if ($action->id !== 'index') {
            if (($data = Craft::$app->getRequest()->getValidatedBodyParam('data')) === null) {
                throw new BadRequestHttpException();
            }

            // Only users with performUpdates permission can install plugins
            $this->requirePermission('performUpdates');

            $this->_data = Json::decode($data);
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
        $request = Craft::$app->getRequest();
        $this->_data['name'] = $request->getRequiredQueryParam('name');
        $this->_data['handle'] = $request->getRequiredQueryParam('handle');
        $this->_data['version'] = $request->getRequiredQueryParam('version');

        // Load the updater JS
        $view = $this->getView();
        $view->registerAssetBundle(UpdaterAsset::class);

        $state = $this->_initialState();
        $state['data'] = $this->_getHashedData();
        $this->getView()->registerJs('Craft.updater = (new Craft.Updater(\'pluginstore/install\')).setState('.Json::encode($state).');');

        return $this->renderTemplate('_special/updater', [
            'title' => Craft::t('app', 'Plugin Installer'),
        ]);
    }

    /**
     * Rechecks for composer.json, if it couldn't be found in the initial state.
     *
     * @return Response
     */
    public function actionRecheckComposer(): Response
    {
        return $this->_send($this->_initialState());
    }

    /**
     * Installs Composer dependencies.
     *
     * @return Response
     */
    public function actionComposerInstall(): Response
    {
        $requirements = [$this->_data['name'] => $this->_data['version']];
        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->install($requirements, $io);
        } catch (\Throwable $e) {
            Craft::error('Error updating Composer requirements: '.$e->getMessage()."\nOutput: ".$io->getOutput(), __METHOD__);
            return $this->_composerError(Craft::t('app', 'Composer was unable to install the updates.'), $e, $io);
        }

        return $this->_next(self::ACTION_OPTIMIZE);
    }

    /**
     * Optimizes the Composer autoloader.
     *
     * @return Response
     */
    public function actionOptimize(): Response
    {
        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->optimize($io);
        } catch (\Throwable $e) {
            Craft::error('Error optimizing the Composer autoloader: '.$e->getMessage()."\nOutput: ".$io->getOutput(), __METHOD__);
            return $this->_send([
                'error' => Craft::t('app', 'Composer was unable to optimize the autoloader.'),
                'errorDetails' => $this->_composerErrorDetails($e, $io),
                'options' => [
                    $this->_actionOption(Craft::t('app', 'Try again'), self::ACTION_OPTIMIZE),
                    $this->_actionOption(Craft::t('app', 'Continue'), self::ACTION_INSTALL),
                ]
            ]);
        }

        return $this->_next(self::ACTION_INSTALL);
    }

    /**
     * Runs pending migrations.
     *
     * @return Response
     */
    public function actionInstall(): Response
    {
        try {
            Craft::$app->getPlugins()->installPlugin($this->_data['handle']);
        } catch (\Throwable $e) {
            $migration = $output = null;

            $info = Craft::$app->getPlugins()->getComposerPluginInfo($this->_data['handle']);
            $pluginName = $info['name'] ?? $this->_data['name'];
            $email = $info['developerEmail'] ?? 'support@craftcms.com';

            if ($e instanceof MigrateException) {
                /** @var \Throwable $e */
                $e = $e->getPrevious();

                if ($e instanceof MigrationException) {
                    /** @var \Throwable|null $previous */
                    $previous = $e->getPrevious();
                    $migration = $e->migration;
                    $output = $e->output;
                    $e = $previous ?? $e;
                }
            }

            Craft::error('Plugin installation failed: '.$e->getMessage(), __METHOD__);

            $eName = $e instanceof YiiException ? $e->getName() : get_class($e);

            return $this->_send([
                'error' => Craft::t('app', 'One of {name}’s migrations failed.', ['name' => $pluginName]),
                'errorDetails' => $eName.': '.$e->getMessage().
                    ($migration ? "\n\nMigration: ".get_class($migration) : '').
                    ($output ? "\n\nOutput:\n\n".$output : ''),
                'options' => [
                    [
                        'label' => Craft::t('app', 'Send for help'),
                        'submit' => true,
                        'email' => $email,
                        'subject' => $pluginName.' update failure',
                    ]
                ],
            ]);
        }

        return $this->_finished();
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

        return $this->asJson(['success' => true]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the initial state for the updater JS.
     *
     * @param bool $force Whether to go through with the update even if Maintenance Mode is enabled
     *
     * @return array
     */
    private function _initialState(bool $force = false): array
    {
        // Make sure we can find composer.json
        try {
            Craft::$app->getComposer()->getJsonPath();
        } catch (\Exception $e) {
            return [
                'error' => Craft::t('app', 'Your composer.json file could not be located. Try setting the CRAFT_COMPOSER_PATH constant in index.php to its location on the server.'),
                'errorDetails' => 'define(\'CRAFT_COMPOSER_PATH\', \'path/to/composer.json\');',
                'options' => [
                    $this->_actionOption(Craft::t('app', 'Try again'), self::ACTION_RECHECK_COMPOSER, ['submit' => true]),
                ]
            ];
        }

        return $this->_actionState(self::ACTION_COMPOSER_INSTALL);
    }

    /**
     * Sends a state response.
     *
     * @param array $state
     *
     * @return Response
     */
    private function _send(array $state = []): Response
    {
        // Encode and hash the data
        $state['data'] = $this->_getHashedData();

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
    private function _next(string $nextAction, array $state = []): Response
    {
        $state = $this->_actionState($nextAction, $state);
        return $this->_send($state);
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
    private function _actionOption(string $label, string $action, array $state = []): array
    {
        $state['label'] = $label;
        return $this->_actionState($action, $state);
    }

    /**
     * Sends a "finished" state response.
     *
     * @param array $state
     *
     * @return Response
     */
    private function _finished(array $state = []): Response
    {
        $state = $this->_finishedState($state);
        return $this->_send($state);
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
    private function _composerError(string $error, \Throwable $e, BufferIO $io, array $state = []): Response
    {
        $state['error'] = $error;
        $state['errorDetails'] = $this->_composerErrorDetails($e, $io);

        $state['options'] = [
            [
                'label' => Craft::t('app', 'Send for help'),
                'submit' => true,
                'email' => 'support@craftcms.com',
                'subject' => 'Craft CMS update failure',
            ]
        ];

        return $this->_send($state);
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

    /**
     * Sets the state info for the given next action.
     *
     * @param string $nextAction
     * @param array  $state
     *
     * @return array
     */
    private function _actionState(string $nextAction, array $state = []): array
    {
        $state['nextAction'] = $nextAction;

        switch ($nextAction) {
            case self::ACTION_RECHECK_COMPOSER:
                $state['status'] = Craft::t('app', 'Checking…');
                break;
            case self::ACTION_COMPOSER_INSTALL:
                $state['status'] = Craft::t('app', 'Loading the plugin (this may take a minute)…');
                break;
            case self::ACTION_OPTIMIZE:
                $state['status'] = Craft::t('app', 'Optimizing…');
                break;
            case self::ACTION_INSTALL:
                $state['status'] = Craft::t('app', 'Installing the plugin…');
                break;
        }

        return $state;
    }

    /**
     * Sets the state info for when the job is done.
     *
     * @param array $state
     *
     * @return array
     */
    private function _finishedState(array $state = []): array
    {
        if (!isset($state['status']) && !isset($state['error'])) {
            $state['status'] = Craft::t('app', 'All done!');
        }

        $state['finished'] = true;
        $state['returnUrl'] = 'plugin-store';

        return $state;
    }

    /**
     * Returns the hashed data for JS.
     *
     * @return string
     */
    private function _getHashedData(): string
    {
        return Craft::$app->getSecurity()->hashData(Json::encode($this->_data));
    }
}
