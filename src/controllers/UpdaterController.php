<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Composer\IO\BufferIO;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Craft;
use craft\errors\InvalidPluginException;
use RequirementsChecker;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * UpdaterController handles the Craft/plugin update workflow.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @internal
 */
class UpdaterController extends BaseUpdaterController
{
    public const ACTION_FORCE_UPDATE = 'force-update';
    public const ACTION_BACKUP = 'backup';
    public const ACTION_SERVER_CHECK = 'server-check';
    public const ACTION_REVERT = 'revert';
    public const ACTION_RESTORE_DB = 'restore-db';
    public const ACTION_MIGRATE = 'migrate';

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if ($action->id === 'index' && $this->request->getBodyParam('install') !== null) {
            // Only users with performUpdates permission can install new versions
            $this->requirePermission('performUpdates');
        }

        return true;
    }

    /**
     * Forces the update even if Craft is already in Maintenance Mode.
     *
     * @return Response
     */
    public function actionForceUpdate(): Response
    {
        return $this->send($this->realInitialState(true));
    }

    /**
     * Backup the database.
     *
     * @return Response
     */
    public function actionBackup(): Response
    {
        try {
            $this->data['dbBackupPath'] = Craft::$app->getDb()->backup();
        } catch (Throwable $e) {
            Craft::error('Error backing up the database: ' . $e->getMessage(), __METHOD__);
            if (!empty($this->data['install'])) {
                $firstAction = $this->actionOption(Craft::t('app', 'Revert the update'), self::ACTION_REVERT);
            } else {
                $firstAction = $this->finishedState([
                    'label' => Craft::t('app', 'Abort the update'),
                    'status' => Craft::t('app', 'Update aborted.'),
                ]);
            }
            return $this->send([
                'error' => Craft::t('app', 'Couldn’t backup the database. How would you like to proceed?'),
                'options' => [
                    $firstAction,
                    $this->actionOption(Craft::t('app', 'Try again'), self::ACTION_BACKUP),
                    $this->actionOption(Craft::t('app', 'Continue anyway'), self::ACTION_MIGRATE),
                ],
            ]);
        }

        return $this->sendNextAction(self::ACTION_MIGRATE);
    }

    /**
     * Restores the database.
     *
     * @return Response
     */
    public function actionRestoreDb(): Response
    {
        try {
            Craft::$app->getDb()->restore($this->data['dbBackupPath']);
        } catch (Throwable $e) {
            Craft::error('Error restoring up the database: ' . $e->getMessage(), __METHOD__);
            return $this->send([
                'error' => Craft::t('app', 'Couldn’t restore the database. How would you like to proceed?'),
                'options' => [
                    $this->actionOption(Craft::t('app', 'Try again'), self::ACTION_RESTORE_DB),
                    $this->actionOption(Craft::t('app', 'Continue anyway'), self::ACTION_MIGRATE),
                ],
            ]);
        }

        // Did we install new versions of things?
        if (!empty($this->data['install'])) {
            return $this->sendNextAction(self::ACTION_REVERT);
        }

        return $this->sendFinished([
            'status' => Craft::t('app', 'The database was restored successfully.'),
        ]);
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
            Craft::$app->getComposer()->install($this->data['current'], $io);
            Craft::info("Reverted Composer requirements.\nOutput: " . $io->getOutput(), __METHOD__);
            $this->data['reverted'] = true;
        } catch (Throwable $e) {
            Craft::error('Error reverting Composer requirements: ' . $e->getMessage() . "\nOutput: " . $io->getOutput(), __METHOD__);
            return $this->sendComposerError(Craft::t('app', 'Composer was unable to revert the updates.'), $e, $io->getOutput());
        }

        return $this->send($this->postComposerInstallState());
    }

    /**
     * Ensures Craft still meets the minimum system requirements
     *
     * @return Response
     */
    public function actionServerCheck(): Response
    {
        $reqCheck = new RequirementsChecker();
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
            Craft::warning("The server doesn't meet Craft's new requirements:\n - " . implode("\n - ", $errors), __METHOD__);
            return $this->send([
                'error' => Craft::t('app', 'The server doesn’t meet Craft’s new requirements:') . ' ' . implode(', ', $errors),
                'options' => [
                    $this->actionOption(Craft::t('app', 'Revert update'), self::ACTION_REVERT),
                    $this->actionOption(Craft::t('app', 'Check again'), self::ACTION_SERVER_CHECK),
                ],
            ]);
        }

        // Are there any migrations to run?
        $installedHandles = array_keys($this->data['install']);
        $pendingHandles = Craft::$app->getUpdates()->getPendingMigrationHandles();

        if (!empty(array_intersect($pendingHandles, $installedHandles))) {
            $backup = Craft::$app->getConfig()->getGeneral()->getBackupOnUpdate();
            return $this->sendNextAction($backup ? self::ACTION_BACKUP : self::ACTION_MIGRATE);
        }

        // Nope - we're done!
        return $this->sendFinished();
    }

    /**
     * Runs pending migrations.
     *
     * @return Response
     */
    public function actionMigrate(): Response
    {
        if (!empty($this->data['install'])) {
            $handles = array_keys($this->data['install']);
        } else {
            $handles = array_merge($this->data['migrate']);
        }

        return $this->runMigrations($handles, self::ACTION_RESTORE_DB) ?? $this->sendFinished();
    }

    /**
     * @inheritdoc
     */
    protected function pageTitle(): string
    {
        return Craft::t('app', 'Updater');
    }

    /**
     * @inheritdoc
     */
    protected function initialData(): array
    {
        // Set the things to install, if any
        if (($install = $this->request->getBodyParam('install')) !== null) {
            $packageNames = $this->request->getRequiredBodyParam('packageNames');

            $data = [
                'install' => $this->_parseInstallParam($install),
                'current' => [],
                'requirements' => [],
                'reverted' => false,
            ];

            // Convert update handles to Composer package names, and capture current versions
            $pluginsService = Craft::$app->getPlugins();

            foreach ($data['install'] as $handle => $version) {
                $packageName = strip_tags($packageNames[$handle]);
                if ($handle === 'craft') {
                    $oldPackageName = 'craftcms/cms';
                    $current = Craft::$app->getVersion();
                } else {
                    $pluginInfo = $pluginsService->getPluginInfo($handle);
                    $oldPackageName = $pluginInfo['packageName'];
                    $current = $pluginInfo['version'];
                }
                $data['current'][$packageName] = $current;
                $data['requirements'][$packageName] = $version;

                // Has the package name changed?
                if ($packageName !== $oldPackageName) {
                    $data['requirements'][$oldPackageName] = false;
                }
            }
        } else {
            // Figure out what needs to be updated, if any
            $data = [
                'migrate' => Craft::$app->getUpdates()->getPendingMigrationHandles(),
            ];
        }

        // Set the return URL, if any
        if (($returnUrl = $this->findReturnUrl()) !== null) {
            $data['returnUrl'] = strip_tags($returnUrl);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function initialState(bool $force = false): array
    {
        // Is there anything to install/update?
        if (empty($this->data['install']) && empty($this->data['migrate'])) {
            return $this->finishedState([
                'status' => Craft::t('app', 'Nothing to update.'),
            ]);
        }

        // Is Craft already in Maintenance Mode?
        if (!$force && Craft::$app->getIsInMaintenanceMode()) {
            // Bail if Craft is already in maintenance mode
            return [
                'error' => str_replace(['<br>', '<br/>'], "\n\n", Craft::t('app', 'It looks like someone is currently performing a system update.<br>Only continue if you’re sure that’s not the case.')),
                'options' => [
                    $this->actionOption(Craft::t('app', 'Continue'), self::ACTION_FORCE_UPDATE, ['submit' => true]),
                ],
            ];
        }

        // If there's anything to install, make sure we can find composer.json
        if (!empty($this->data['install']) && !$this->ensureComposerJson()) {
            return $this->noComposerJsonState();
        }

        // Enable maintenance mode
        Craft::$app->enableMaintenanceMode();

        if (!empty($this->data['install'])) {
            $nextAction = self::ACTION_COMPOSER_INSTALL;
        } else {
            $backup = Craft::$app->getConfig()->getGeneral()->getBackupOnUpdate();
            $nextAction = $backup ? self::ACTION_BACKUP : self::ACTION_MIGRATE;
        }

        return $this->actionState($nextAction);
    }

    /**
     * @inheritdoc
     */
    protected function postComposerInstallState(): array
    {
        // Was this after a revert?
        if ($this->data['reverted']) {
            return $this->actionState(self::ACTION_FINISH, [
                'status' => Craft::t('app', 'The update was reverted successfully.'),
            ]);
        }

        return $this->actionState(self::ACTION_SERVER_CHECK);
    }

    /**
     * Returns the return URL that should be passed with a finished state.
     *
     * @return string
     */
    protected function returnUrl(): string
    {
        return $this->data['returnUrl'] ?? Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect();
    }

    /**
     * @inheritdoc
     */
    protected function actionStatus(string $action): string
    {
        return match ($action) {
            self::ACTION_FORCE_UPDATE => Craft::t('app', 'Updating…'),
            self::ACTION_BACKUP => Craft::t('app', 'Backing-up database…'),
            self::ACTION_RESTORE_DB => Craft::t('app', 'Restoring database…'),
            self::ACTION_MIGRATE => Craft::t('app', 'Updating database…'),
            self::ACTION_REVERT => Craft::t('app', 'Reverting update (this may take a minute)…'),
            self::ACTION_SERVER_CHECK => Craft::t('app', 'Checking server requirements…'),
            default => parent::actionStatus($action),
        };
    }

    /**
     * @inheritdoc
     */
    protected function sendFinished(array $state = []): Response
    {
        // Disable maintenance mode
        Craft::$app->disableMaintenanceMode();

        return parent::sendFinished($state);
    }

    /**
     * Parses the 'install` param and returns handle => version pairs.
     *
     * @param array $installParam
     * @return array
     * @throws BadRequestHttpException
     */
    private function _parseInstallParam(array $installParam): array
    {
        $install = [];

        foreach ($installParam as $handle => $version) {
            $handle = strip_tags($handle);
            $version = strip_tags($version);
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
     * @return bool
     * @throws BadRequestHttpException if the handle is invalid
     */
    private function _canUpdate(string $handle, string $toVersion): bool
    {
        if ($handle === 'craft') {
            $fromVersion = Craft::$app->getVersion();
        } else {
            $pluginInfo = null;
            try {
                $pluginInfo = Craft::$app->getPlugins()->getPluginInfo($handle);
            } catch (InvalidPluginException) {
            }

            if ($pluginInfo === null || !$pluginInfo['isInstalled']) {
                throw new BadRequestHttpException('Invalid update handle: ' . $handle);
            }
            $fromVersion = $pluginInfo['version'];
        }

        // Normalize the versions in case only one of them starts with a 'v' or something
        $vp = new VersionParser();
        $toVersion = $vp->normalize($toVersion);
        $fromVersion = $vp->normalize($fromVersion);

        return Comparator::greaterThan($toVersion, $fromVersion);
    }
}
