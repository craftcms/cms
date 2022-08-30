<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Composer\IO\BufferIO;
use Craft;
use craft\errors\MigrateException;
use craft\errors\MigrationException;
use craft\helpers\App;
use craft\helpers\Json;
use craft\web\assets\updater\UpdaterAsset;
use craft\web\Controller;
use craft\web\Response as CraftResponse;
use Throwable;
use yii\base\Exception;
use yii\base\Exception as YiiException;
use yii\web\BadRequestHttpException;
use yii\web\JsonResponseFormatter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * BaseUpdaterController provides the base class for Craft/plugin installation/updating/removal.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @internal
 */
abstract class BaseUpdaterController extends Controller
{
    public const ACTION_PRECHECK = 'precheck';
    public const ACTION_RECHECK_COMPOSER = 'recheck-composer';
    public const ACTION_COMPOSER_INSTALL = 'composer-install';
    public const ACTION_COMPOSER_REMOVE = 'composer-remove';
    public const ACTION_FINISH = 'finish';

    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE;

    /**
     * @var array The data associated with the current update
     */
    protected array $data = [];

    /**
     * @inheritdoc
     * @throws NotFoundHttpException if it’s not a control panel request
     * @throws BadRequestHttpException if there’s invalid data in the request
     */
    public function beforeAction($action): bool
    {
        // This controller is only available to the control panel
        if (!$this->request->getIsCpRequest()) {
            throw new NotFoundHttpException();
        }

        $this->requirePostRequest();

        if (!parent::beforeAction($action)) {
            return false;
        }

        if ($action->id !== 'index') {
            if (($data = $this->request->getValidatedBodyParam('data')) === null) {
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
        $state = $this->realInitialState();
        $state['data'] = $this->_hashedData();
        $idJs = Json::encode($this->id);
        $stateJs = Json::encode($state);
        $this->getView()->registerJs("Craft.updater = (new Craft.Updater($idJs)).setState($stateJs);");

        return $this->renderTemplate('_special/updater.twig', [
            'title' => $this->pageTitle(),
        ]);
    }

    /**
     * Ensures that PHP’s memory_limit and max_execution_time settings are high enough to run Composer.
     *
     * @return Response
     */
    public function actionPrecheck(): Response
    {
        $postState = $this->data['postPrecheckState'];

        if (!App::testIniSet()) {
            $errors = [];

            $timeLimit = (int)trim(ini_get('max_execution_time'));
            if ($timeLimit !== 0 && $timeLimit < 120) {
                $errors[] = Craft::t('app', '{name} should be at least {value}.', [
                    'name' => '`max_execution_time`',
                    'value' => '`120`',
                ]);
            }

            $memoryLimit = App::phpConfigValueInBytes('memory_limit');
            if ($memoryLimit !== -1 && $memoryLimit < 1024 * 1024 * 256) {
                $errors[] = Craft::t('app', '{name} should be at least {value}.', [
                    'name' => '`memory_limit`',
                    'value' => '`256M`',
                ]);
            }

            if (!empty($errors)) {
                $error = Craft::t('app', 'Please fix the following in your {file} file before proceeding:', ['file' => '`php.ini`']) .
                    "\n\n" . implode("\n\n", $errors);

                return $this->send([
                    'error' => $error,
                    'options' => [
                        ['label' => Craft::t('app', 'Learn how'), 'url' => 'https://craftcms.com/knowledge-base/php-ini'],
                        $this->actionOption(Craft::t('app', 'Check again'), self::ACTION_PRECHECK),
                        $this->actionOption(Craft::t('app', 'Continue anyway'), $postState['nextAction'], $postState),
                    ],
                ]);
            }
        }

        return $this->send($postState);
    }

    /**
     * Rechecks for composer.json, if it couldn't be found in the initial state.
     *
     * @return Response
     */
    public function actionRecheckComposer(): Response
    {
        return $this->send($this->realInitialState());
    }

    /**
     * Installs Composer dependencies.
     *
     * @return Response
     */
    public function actionComposerInstall(): Response
    {
        // Preload JsonResponseFormatter because the Yii 2.0.44 version requires a newer version of yii\helper\BaseJson
        class_exists(JsonResponseFormatter::class);

        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->install($this->data['requirements'], $io);
            Craft::info("Updated Composer requirements.\nOutput: " . $io->getOutput(), __METHOD__);
        } catch (Throwable $e) {
            Craft::error('Error updating Composer requirements: ' . $e->getMessage() . "\nOutput: " . $io->getOutput(), __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);

            $output = $io->getOutput();
            if (str_contains($output, 'Your requirements could not be resolved to an installable set of packages.')) {
                $error = Craft::t('app', 'Composer was unable to install the updates due to a dependency conflict.');
            } else {
                $error = Craft::t('app', 'Composer was unable to install the updates.');
            }

            return $this->sendComposerError($error, $e, $output);
        }

        return $this->send($this->postComposerInstallState());
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
            Craft::info("Updated Composer requirements.\nOutput: " . $io->getOutput(), __METHOD__);
            $this->data['removed'] = true;
        } catch (Throwable $e) {
            Craft::error('Error updating Composer requirements: ' . $e->getMessage() . "\nOutput: " . $io->getOutput(), __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            return $this->sendComposerError(Craft::t('app', 'Composer was unable to remove the plugin.'), $e, $io->getOutput());
        }

        return $this->send($this->postComposerInstallState());
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
     * @param bool $force Whether to go through with the update even if Maintenance Mode is enabled
     * @return array
     */
    abstract protected function initialState(bool $force = false): array;

    /**
     * Returns the real initial state for the updater JS.
     *
     * @param bool $force Whether to go through with the update even if Maintenance Mode is enabled
     * @return array
     */
    final protected function realInitialState(bool $force = false): array
    {
        $state = $this->initialState($force);

        // If there's nothing to do here, then no precheck is needed
        if (!isset($state['nextAction'])) {
            return $state;
        }

        // Store the "initial" state in a postPrecheckState, and make the real initial state the precheck
        $this->data['postPrecheckState'] = $state;
        return [
            'nextAction' => self::ACTION_PRECHECK,
            'status' => $this->actionStatus(self::ACTION_PRECHECK),
        ];
    }

    /**
     * Returns the state data for after [[actionComposerInstall()]] is done.
     *
     * @return array
     */
    abstract protected function postComposerInstallState(): array;

    /**
     * Returns the return URL provided by the `return` body param, if it’s a valid URL.
     *
     * @return string|null
     * @throws BadRequestHttpException if the `return` body param isn’t a valid URL.
     * @since 3.7.17
     */
    protected function findReturnUrl(): ?string
    {
        $returnUrl = $this->request->getBodyParam('return');
        if ($returnUrl === null) {
            return null;
        }
        if (str_contains($returnUrl, '{')) {
            throw new BadRequestHttpException("Invalid return URL: $returnUrl");
        }
        return $returnUrl;
    }

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
    protected function ensureComposerJson(): bool
    {
        try {
            Craft::$app->getComposer()->getJsonPath();
            return true;
        } catch (\Exception) {
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
            ],
        ];
    }

    /**
     * Sends a state response.
     *
     * @param array $state
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
     * @param array $state
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
     * @param string $error The status message to show
     * @param Throwable $e The exception that was thrown
     * @param string $output The Composer output
     * @param array $state
     * @return Response
     */
    protected function sendComposerError(string $error, Throwable $e, string $output, array $state = []): Response
    {
        $state['error'] = $error;
        $state['errorDetails'] = $this->_composerErrorDetails($e, $output);

        $state['options'] = [
            [
                'label' => Craft::t('app', 'Troubleshoot'),
                'url' => 'https://craftcms.com/knowledge-base/failed-updates',
            ],
            [
                'label' => Craft::t('app', 'Send for help'),
                'email' => 'support@craftcms.com',
                'subject' => 'Composer error',
            ],
        ];

        return $this->send($state);
    }

    /**
     * Returns an option definition that kicks off a new action.
     *
     * @param string $label
     * @param string $action
     * @param array $state
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
     * @param array $state
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
     * @return string
     * @throws Exception if $action isn't valid
     */
    protected function actionStatus(string $action): string
    {
        return match ($action) {
            self::ACTION_PRECHECK => Craft::t('app', 'Checking environment…'),
            self::ACTION_RECHECK_COMPOSER => Craft::t('app', 'Checking…'),
            self::ACTION_COMPOSER_INSTALL => Craft::t('app', 'Updating Composer dependencies (this may take a minute)…', [
                'command' => '`composer install`',
            ]),
            self::ACTION_COMPOSER_REMOVE => Craft::t('app', 'Updating Composer dependencies (this may take a minute)…', [
                'command' => '`composer remove`',
            ]),
            self::ACTION_FINISH => Craft::t('app', 'Finishing up…'),
            default => throw new Exception('Invalid action: ' . $action),
        };
    }

    /**
     * Sets the state info for when the job is done.
     *
     * @param array $state
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

    /**
     * Runs the migrations for a given list of handles.
     *
     * @param string[] $handles
     * @param string|null $restoreAction
     * @return Response|null
     */
    protected function runMigrations(array $handles, ?string $restoreAction = null): ?Response
    {
        try {
            Craft::$app->getUpdates()->runMigrations($handles);
        } catch (MigrateException $e) {
            $ownerName = $e->ownerName;
            $ownerHandle = $e->ownerHandle;
            /** @var Throwable $e */
            $e = $e->getPrevious();

            if ($e instanceof MigrationException) {
                /** @var Throwable|null $previous */
                $previous = $e->getPrevious();
                $migration = $e->migration;
                $output = $e->output;
                $error = get_class($migration) . ' migration failed' . ($previous ? ': ' . $previous->getMessage() : '.');
                $e = $previous ?? $e;
            } else {
                $migration = $output = null;
                $error = 'Migration failed: ' . $e->getMessage();
            }

            Craft::error($error, __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);

            $options = [];

            // Do we have a database backup to restore?
            if ($restoreAction !== null && !empty($this->data['dbBackupPath'])) {
                if (!empty($this->data['install'])) {
                    $restoreLabel = Craft::t('app', 'Revert update');
                } else {
                    $restoreLabel = Craft::t('app', 'Restore database');
                }
                $options[] = $this->actionOption($restoreLabel, $restoreAction);
            }

            $options[] = [
                'label' => Craft::t('app', 'Troubleshoot'),
                'url' => 'https://craftcms.com/knowledge-base/failed-updates',
            ];

            if ($ownerHandle !== 'craft' && ($plugin = Craft::$app->getPlugins()->getPlugin($ownerHandle)) !== null) {
                $email = $plugin->developerEmail;
            }
            $email = $email ?? 'support@craftcms.com';

            $options[] = [
                'label' => Craft::t('app', 'Send for help'),
                'email' => $email,
                'subject' => $ownerName . ' update failure',
            ];

            $eName = $e instanceof Exception ? $e->getName() : get_class($e);

            return $this->send([
                'error' => Craft::t('app', 'One of {name}’s migrations failed.', ['name' => $ownerName]),
                'errorDetails' => $eName . ': ' . $e->getMessage() .
                    ($migration ? "\n\nMigration: " . get_class($migration) : '') .
                    ($output ? "\n\nOutput:\n\n" . $output : ''),
                'options' => $options,
            ]);
        }

        return null;
    }

    /**
     * Attempts to install a plugin by its handle.
     *
     * @param string $handle
     * @param string|null $edition
     * @return array Array with installation results
     */
    protected function installPlugin(string $handle, ?string $edition = null): array
    {
        // Prevent the plugin from sending any headers, etc.
        $response = $this->response;
        $tempResponse = new CraftResponse(['isSent' => true]);
        Craft::$app->set('response', $tempResponse);
        $this->response = $tempResponse;

        try {
            Craft::$app->getPlugins()->installPlugin($handle, $edition);
            $success = true;
            $errorDetails = null;
        } catch (Throwable $e) {
            $success = false;
            Craft::$app->set('response', $response);
            $this->response = $response;
            $migration = $output = null;

            if ($e instanceof MigrateException) {
                /** @var Throwable $e */
                $e = $e->getPrevious();
                if ($e instanceof MigrationException) {
                    /** @var Throwable|null $previous */
                    $previous = $e->getPrevious();
                    $migration = $e->migration;
                    $output = $e->output;
                    $e = $previous ?? $e;
                }
            }

            Craft::error('Plugin installation failed: ' . $e->getMessage(), __METHOD__);

            $eName = $e instanceof YiiException ? $e->getName() : get_class($e);
            $errorDetails = $eName . ': ' . $e->getMessage() .
                ($migration ? "\n\nMigration: " . get_class($migration) : '') .
                ($output ? "\n\nOutput:\n\n" . $output : '');
        }

        // Put the real response back
        Craft::$app->set('response', $response);
        $this->response = $response;

        return [$success, $tempResponse, $errorDetails];
    }

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
     * @param Throwable $e The exception that was thrown
     * @param string $output The Composer output
     * @return string
     */
    private function _composerErrorDetails(Throwable $e, string $output): string
    {
        $details = [];

        $message = trim($e->getMessage());
        if ($message && $message !== 'An error occurred') {
            $details[] = Craft::t('app', 'Error:') . ' ' . $message;
        }

        $output = trim(strip_tags($output));
        if ($output) {
            $details[] = Craft::t('app', 'Composer output:') . ' ' . $output;
        }

        if (empty($details)) {
            $details[] = 'Exception of class ' . get_class($e) . ' was thrown.';
        }

        return implode("\n\n", $details);
    }
}
