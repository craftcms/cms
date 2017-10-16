<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Composer\IO\BufferIO;
use Craft;
use craft\base\Plugin;
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
class UpdaterController extends Controller
{
    // Constants
    // =========================================================================

    const ACTION_FORCE_UPDATE = 'force-update';
    const ACTION_RECHECK_COMPOSER = 'recheck-composer';
    const ACTION_BACKUP = 'backup';
    const ACTION_INSTALL = 'install';
    const ACTION_OPTIMIZE = 'optimize';
    const ACTION_SERVER_CHECK = 'server-check';
    const ACTION_REVERT = 'revert';
    const ACTION_RESTORE_DB = 'restore-db';
    const ACTION_MIGRATE = 'migrate';

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

            // Only users with performUpdates permission can install new versions
            if (!empty($data['install'])) {
                $this->requirePermission('performUpdates');
            }

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

        // Set the things to install, if any
        if (($install = $request->getQueryParam('install')) !== null) {
            $this->_data['install'] = $this->_parseInstallParam($install);
            $this->_data['reverted'] = false;
        } else {
            // Figure out what needs to be updated, if any
            $this->_data['migrate'] = Craft::$app->getUpdates()->getPendingMigrationHandles();
        }

        // Set the return URL, if any
        if (($returnUrl = $request->getQueryParam('return')) !== null) {
            $this->_data['returnUrl'] = $returnUrl;
        }

        // Load the updater JS
        $view = $this->getView();
        $view->registerAssetBundle(UpdaterAsset::class);
        $view->registerTranslations('app', [
            'A fatal error has occurred:',
            'Status:',
            'Response:',
            'Send for help',
        ]);

        $state = $this->_initialState();
        $state['data'] = $this->_getHashedData();
        $this->getView()->registerJs('Craft.updater = (new Craft.Updater()).setState('.Json::encode($state).');');

        return $this->renderTemplate('_special/updates/go');
    }

    /**
     * Forces the update even if Craft is already in Maintenance Mode.
     *
     * @return Response
     */
    public function actionForceUpdate(): Response
    {
        return $this->_send($this->_initialState(true));
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
     * Backup the database.
     *
     * @return Response
     */
    public function actionBackup(): Response
    {
        try {
            $this->_data['dbBackupPath'] = Craft::$app->getDb()->backup();
        } catch (\Throwable $e) {
            Craft::error('Error backing up the database: '.$e->getMessage(), __METHOD__);
            if (!empty($this->_data['install'])) {
                $firstAction = $this->_actionOption(Craft::t('app', 'Revert the update'), self::ACTION_REVERT);
            } else {
                $firstAction = $this->_finishedState([
                    'label' => Craft::t('app', 'Abort the update'),
                    'status' => Craft::t('app', 'Update aborted.')
                ]);
            }
            return $this->_send([
                'error' => Craft::t('app', 'Couldn’t backup the database. How would you like to proceed?'),
                'options' => [
                    $firstAction,
                    $this->_actionOption(Craft::t('app', 'Try again'), self::ACTION_BACKUP),
                    $this->_actionOption(Craft::t('app', 'Continue anyway'), self::ACTION_MIGRATE),
                ]
            ]);
        }

        return $this->_next(self::ACTION_MIGRATE);
    }

    /**
     * Restores the database.
     *
     * @return Response
     */
    public function actionRestoreDb(): Response
    {
        try {
            Craft::$app->getDb()->restore($this->_data['dbBackupPath']);
        } catch (\Throwable $e) {
            Craft::error('Error restoring up the database: '.$e->getMessage(), __METHOD__);
            return $this->_send([
                'error' => Craft::t('app', 'Couldn’t restore the database. How would you like to proceed?'),
                'options' => [
                    $this->_actionOption(Craft::t('app', 'Try again'), self::ACTION_RESTORE_DB),
                    $this->_actionOption(Craft::t('app', 'Continue anyway'), self::ACTION_MIGRATE),
                ]
            ]);
        }

        // Did we install new versions of things?
        if (!empty($this->_data['install'])) {
            return $this->_next(self::ACTION_REVERT);
        }

        return $this->_finished([
            'status' => Craft::t('app', 'The database was restored successfully.'),
        ]);
    }

    /**
     * Installs Composer dependencies.
     *
     * @return Response
     */
    public function actionInstall(): Response
    {
        // Convert update handles to Composer package names, and capture current versions
        $requirements = [];
        $this->_data['current'] = [];

        foreach ($this->_data['install'] as $handle => $version) {
            if ($handle === 'craft') {
                $packageName = 'craftcms/cms';
                $current = Craft::$app->getVersion();
            } else {
                /** @var Plugin $plugin */
                $plugin = Craft::$app->getPlugins()->getPlugin($handle);
                $packageName = $plugin->packageName;
                $current = $plugin->getVersion();
            }
            $requirements[$packageName] = $version;
            $this->_data['current'] = $current;
        }


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
     * Reverts the site to its previous Composer package versions.
     *
     * @return Response
     */
    public function actionRevert(): Response
    {
        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->install($this->_data['current'], $io);
            $this->_data['reverted'] = true;
        } catch (\Throwable $e) {
            Craft::error('Error reverting Composer requirements: '.$e->getMessage()."\nOutput: ".$io->getOutput(), __METHOD__);
            return $this->_composerError(Craft::t('app', 'Composer was unable to revert the updates.'), $e, $io);
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
                    $this->_actionOption(Craft::t('app', 'Continue'), self::ACTION_SERVER_CHECK),
                ]
            ]);
        }

        // Was this after a revert?
        if ($this->_data['reverted']) {
            $this->_finished([
                'status' => Craft::t('app', 'The update was reverted successfully.'),
            ]);
        }

        return $this->_next(self::ACTION_SERVER_CHECK);
    }

    /**
     * Ensures Craft still meets the minimum system requirements
     *
     * @return Response
     */
    public function actionServerCheck(): Response
    {
        $reqCheck = new \RequirementsChecker();
        $reqCheck->checkCraft();

        $errors = [];

        if ($reqCheck->result['summary']['errors'] > 0) {
            foreach ($reqCheck->getResult()['requirements'] as $req) {
                if ($req['failed'] === true) {
                    $errors[] = $req['memo'];
                }
            }
        }

        if (!empty($errors)) {
            Craft::warning("The server doesn't meet Craft's new requirements:\n - ".implode("\n - ", $errors), __METHOD__);
            return $this->_send([
                'error' => Craft::t('app', 'The server doesn’t meet Craft’s new requirements:').' '.implode(', ', $errors),
                'options' => [
                    $this->_actionOption(Craft::t('app', 'Revert update'), self::ACTION_REVERT),
                    $this->_actionOption(Craft::t('app', 'Check again'), self::ACTION_SERVER_CHECK),
                ]
            ]);
        }

        // Are there any migrations to run?
        $installedHandles = array_keys($this->_data['install']);
        $pendingHandles = Craft::$app->getUpdates()->getPendingMigrationHandles();
        if (!empty(array_intersect($pendingHandles, $installedHandles))) {
            $backup = Craft::$app->getConfig()->getGeneral()->getBackupOnUpdate();
            return $this->_next($backup ? self::ACTION_BACKUP : self::ACTION_MIGRATE);
        }

        // Nope - we're done!
        return $this->_finished();
    }

    /**
     * Runs pending migrations.
     *
     * @return Response
     */
    public function actionMigrate(): Response
    {
        if (!empty($this->_data['install'])) {
            $handles = array_keys($this->_data['install']);
        } else {
            $handles = array_merge($this->_data['migrate']);
        }

        try {
            Craft::$app->getUpdates()->runMigrations($handles);
        } catch (MigrateException $e) {
            $ownerName = $e->ownerName;
            $ownerHandle = $e->ownerHandle;
            /** @var \Throwable $e */
            $e = $e->getPrevious();

            if ($e instanceof MigrationException) {
                /** @var \Throwable|null $previous */
                $previous = $e->getPrevious();
                $migration = $e->migration;
                $output = $e->output;
                $error = get_class($migration).' migration failed'.($previous ? ': '.$previous->getMessage() : '.');
                $e = $previous ?? $e;
            } else {
                $migration = $output = null;
                $error = 'Migration failed: '.$e->getMessage();
            }

            Craft::error($error, __METHOD__);

            $options = [];

            // Do we have a database backup to restore?
            if (!empty($this->_data['dbBackupPath'])) {
                $restoreLabel = !empty($this->_data['install']) ? Craft::t('app', 'Revert update') : Craft::t('app', 'Restore database');
                $options[] = $this->_actionOption($restoreLabel, self::ACTION_RESTORE_DB);
            }

            if ($ownerHandle !== 'craft' && ($plugin = Craft::$app->getPlugins()->getPlugin($ownerHandle)) !== null) {
                /** @var Plugin $plugin */
                $email = $plugin->developerEmail;
            }
            $email = $email ?? 'support@craftcms.com';

            $options[] = [
                'label' => Craft::t('app', 'Send for help'),
                'submit' => true,
                'email' => $email,
                'subject' => $ownerName.' update failure',
            ];

            $eName = $e instanceof YiiException ? $e->getName() : get_class($e);

            return $this->_send([
                'error' => Craft::t('app', 'One of {name}’s migrations failed.', ['name' => $ownerName]),
                'errorDetails' => $eName.': '.$e->getMessage().
                    ($migration ? "\n\nMigration: ".get_class($migration) : '').
                    ($output ? "\n\nOutput:\n\n".$output : ''),
                'options' => $options,
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
     * Parses the 'install` param and returns handle => version pairs.
     *
     * @param string $installParam
     *
     * @return array
     * @throws BadRequestHttpException
     */
    private function _parseInstallParam(string $installParam): array
    {
        $install = [];
        $pairs = explode(',', $installParam);

        foreach ($pairs as $pair) {
            if (strpos($pair, ':') === false) {
                throw new BadRequestHttpException('Updates must be specified in the format "handle:version".');
            }

            list($handle, $version) = explode(':', $pair);
            if ($this->_canUpdate($handle, $version)) {
                $install[$handle] = $version;
            }
        }

        return $install;
    }

    /**
     * Returns whether Craft/a plugin can be updated to a given version.
     *
     * @param string $handle
     * @param string $toVersion
     *
     * @return bool
     * @throws BadRequestHttpException if the handle is invalid
     */
    private function _canUpdate(string $handle, string $toVersion): bool
    {
        if ($handle === 'craft') {
            $fromVersion = Craft::$app->getVersion();
        } else {
            /** @var Plugin|null $plugin */
            if (($plugin = Craft::$app->getPlugins()->getPlugin($handle)) === null) {
                throw new BadRequestHttpException('Invalid update handle: '.$handle);
            }
            $fromVersion = $plugin->getVersion();
        }

        return version_compare($toVersion, $fromVersion, '>');
    }

    /**
     * Returns the initial state for the updater JS.
     *
     * @param bool $force Whether to go through with the update even if Maintenance Mode is enabled
     *
     * @return array
     */
    private function _initialState(bool $force = false): array
    {
        // Is there anything to install/update?
        if (empty($this->_data['install']) && empty($this->_data['migrate'])) {
            return $this->_finishedState([
                'status' => Craft::t('app', 'Nothing to update.')
            ]);
        }

        // Is Craft already in Maintenance Mode?
        if (!$force && Craft::$app->getIsInMaintenanceMode()) {
            // Bail if Craft is already in maintenance mode
            return [
                'error' => str_replace('<br>', "\n\n", Craft::t('app', 'It looks like someone is currently performing a system update.<br>Only continue if you’re sure that’s not the case.')),
                'options' => [
                    $this->_actionOption(Craft::t('app', 'Continue'), self::ACTION_FORCE_UPDATE, ['submit' => true]),
                ]
            ];
        }

        // If there's anything to install, make sure we're set up to use Composer
        if (!empty($this->_data['install'])) {
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
        }

        // Enable maintenance mode
        Craft::$app->enableMaintenanceMode();

        if (!empty($this->_data['install'])) {
            $nextAction = self::ACTION_INSTALL;
        } else {
            $backup = Craft::$app->getConfig()->getGeneral()->getBackupOnUpdate();
            $nextAction = $backup ? self::ACTION_BACKUP : self::ACTION_MIGRATE;
        }

        return $this->_actionState($nextAction);
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
        // Disable maintenance mode
        Craft::$app->disableMaintenanceMode();

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
            case self::ACTION_FORCE_UPDATE:
                $state['status'] = Craft::t('app', 'Updating…');
                break;
            case self::ACTION_RECHECK_COMPOSER:
                $state['status'] = Craft::t('app', 'Checking…');
                break;
            case self::ACTION_BACKUP:
                $state['status'] = Craft::t('app', 'Backing-up database…');
                break;
            case self::ACTION_RESTORE_DB:
                $state['status'] = Craft::t('app', 'Restoring database…');
                break;
            case self::ACTION_MIGRATE:
                $state['status'] = Craft::t('app', 'Updating database…');
                break;
            case self::ACTION_INSTALL:
                $state['status'] = Craft::t('app', 'Installing update (this may take a minute)…');
                break;
            case self::ACTION_REVERT:
                $state['status'] = Craft::t('app', 'Reverting update (this may take a minute)…');
                break;
            case self::ACTION_OPTIMIZE:
                $state['status'] = Craft::t('app', 'Optimizing installation…');
                break;
            case self::ACTION_SERVER_CHECK:
                $state['status'] = Craft::t('app', 'Checking server requirements…');
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
        $state['returnUrl'] = $this->_getReturnUrl();

        return $state;
    }

    /**
     * Returns the return URL that should be passed with a finished state.
     *
     * @return string
     */
    private function _getReturnUrl(): string
    {
        return $this->_data['returnUrl'] ?? Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect();

        // New major Craft CMS version?
        //if ($handle === 'craft' && $oldVersion !== false && App::majorVersion($oldVersion) < App::majorVersion(Craft::$app->version)) {
        //    $returnUrl = UrlHelper::url('whats-new');
        //} else {
        //    $returnUrl = Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect();
        //}
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
