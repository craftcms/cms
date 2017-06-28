<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\base\Plugin;
use craft\config\GeneralConfig;
use craft\enums\PluginUpdateStatus;
use craft\errors\EtException;
use craft\errors\InvalidPluginException;
use craft\errors\UpdateValidationException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\Update;
use craft\helpers\UrlHelper;
use craft\models\PluginUpdate;
use craft\web\assets\updater\UpdaterAsset;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The UpdateController class is a controller that handles various update related tasks such as checking for available
 * updates and running manual and auto-updates.
 *
 * Note that all actions in the controller, except for [[actionPrepare]], [[actionBackupDatabase]],
 * [[actionUpdateDatabase]], [[actionCleanUp]] and [[actionRollback]] require an authenticated Craft session
 * via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UpdateController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = [
        'go',
        'prepare',
        'backup-database',
        'update-database',
        'clean-up',
        'rollback',
        'run-pending-migrations',
    ];

    // Public Methods
    // =========================================================================

    /**
     * Update Index
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        // Redirect to the utility page
        return $this->redirect('utilities/updates');
    }

    /**
     * Update kickoff
     *
     * @param string $update The thing(s) to update, in the format "craft:1.2.3,plugin-handle:1.2.3"
     *
     * @return Response
     */
    public function actionGo(string $update): Response
    {
        $view = $this->getView();

        $view->registerAssetBundle(UpdaterAsset::class);

        $this->getView()->registerTranslations('app', [
            'Unable to determine what to update.',
            'A fatal error has occurred:',
            'Status:',
            'Response:',
            'Send for help',
            'All done!',
            'Craft CMS was unable to install this update :(',
            'The site has been restored to the state it was in before the attempted update.',
            'No files have been updated and the database has not been touched.',
        ]);

        $security = Craft::$app->getSecurity();
        $dataJs = Json::encode([
            'update' => $security->hashData($update),
            'manualUpdate' => (Craft::$app->getRequest()->getSegment(1) === 'manualupdate') ? 1 : 0
        ]);
        $js = <<<EOD
//noinspection JSUnresolvedVariable
new Craft.Updater({$dataJs});
EOD;

        $this->getView()->registerJs($js);

        return $this->renderTemplate('_special/updates/go');
    }

    // Auto Updates
    // -------------------------------------------------------------------------

    /**
     * Called during both a manual and auto-update.
     *
     * @return Response
     */
    public function actionPrepare(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $updates = $this->_getRequestedUpdates($data);

        $manual = false;
        if (!$this->_isManualUpdate($data)) {
            // If it's not a manual update, make sure they have auto-update permissions.
            $this->requirePermission('performUpdates');

            if (!$this->_allowAutoUpdates()) {
                return $this->asJson([
                    'errorDetails' => Craft::t('app', 'Auto-updating is disabled on this system.'),
                    'finished' => true
                ]);
            }
        } else {
            $manual = true;
        }

        $return = Craft::$app->getUpdates()->prepareUpdate($manual, $handle);
        $data['handle'] = Craft::$app->getSecurity()->hashData($handle);

        if (!$return['success']) {
            return $this->asJson([
                'errorDetails' => $return['message'],
                'finished' => true
            ]);
        }

        if ($manual) {
            return $this->_getFirstDbUpdateResponse($data);
        }

        $data['md5'] = Craft::$app->getSecurity()->hashData($return['md5']);

        return $this->asJson([
            'nextStatus' => Craft::t('app', 'Downloading update…'),
            'nextAction' => 'update/process-download',
            'data' => $data
        ]);
    }

    /**
     * Called during an auto-update.
     *
     * @return Response
     * @throws UpdateValidationException
     */
    public function actionProcessDownload(): Response
    {
        // This method should never be called in a manual update.
        $this->requirePermission('performUpdates');

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (!$this->_allowAutoUpdates()) {
            return $this->asJson([
                'errorDetails' => Craft::t('app', 'Auto-updating is disabled on this system.'),
                'finished' => true
            ]);
        }

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getRequestedUpdates($data);

        $md5 = Craft::$app->getSecurity()->validateData($data['md5']);

        if ($md5 === false) {
            throw new UpdateValidationException('Could not validate MD5.');
        }

        $return = Craft::$app->getUpdates()->processUpdateDownload($md5, $handle);

        if (!$return['success']) {
            return $this->asJson([
                'errorDetails' => $return['message'],
                'finished' => true
            ]);
        }

        $data = [
            'handle' => Craft::$app->getSecurity()->hashData($handle),
            'uid' => Craft::$app->getSecurity()->hashData($return['uid']),
        ];

        return $this->asJson([
            'nextStatus' => Craft::t('app', 'Backing-up files…'),
            'nextAction' => 'update/backup-files',
            'data' => $data
        ]);
    }

    /**
     * Called during an auto-update.
     *
     * @return Response
     * @throws UpdateValidationException
     */
    public function actionBackupFiles(): Response
    {
        // This method should never be called in a manual update.
        $this->requirePermission('performUpdates');

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (!$this->_allowAutoUpdates()) {
            return $this->asJson([
                'errorDetails' => Craft::t('app', 'Auto-updating is disabled on this system.'),
                'finished' => true
            ]);
        }

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getRequestedUpdates($data);

        $uid = Craft::$app->getSecurity()->validateData($data['uid']);

        if ($uid === false) {
            throw new UpdateValidationException('Could not validate UID.');
        }

        $return = Craft::$app->getUpdates()->backupFiles($uid, $handle);

        if (!$return['success']) {
            return $this->asJson([
                'errorDetails' => $return['message'],
                'finished' => true
            ]);
        }

        return $this->asJson([
            'nextStatus' => Craft::t('app', 'Updating files…'),
            'nextAction' => 'update/update-files',
            'data' => $data
        ]);
    }

    /**
     * Called during an auto-update.
     *
     * @return Response
     * @throws UpdateValidationException
     */
    public function actionUpdateFiles(): Response
    {
        // This method should never be called in a manual update.
        $this->requirePermission('performUpdates');

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (!$this->_allowAutoUpdates()) {
            return $this->asJson([
                'errorDetails' => Craft::t('app', 'Auto-updating is disabled on this system.'),
                'finished' => true
            ]);
        }

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getRequestedUpdates($data);

        $uid = Craft::$app->getSecurity()->validateData($data['uid']);

        if ($uid === false) {
            throw new UpdateValidationException('Could not validate UID.');
        }

        $return = Craft::$app->getUpdates()->updateFiles($uid, $handle);

        if (!$return['success']) {
            return $this->asJson([
                'errorDetails' => $return['message'],
                'nextStatus' => Craft::t('app', 'An error occurred. Rolling back…'),
                'nextAction' => 'update/rollback'
            ]);
        }

        return $this->_getFirstDbUpdateResponse($data);
    }

    /**
     * Called during both a manual and auto-update.
     *
     * @return Response
     */
    public function actionBackupDatabase(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getRequestedUpdates($data);

        if (true || $this->_shouldBackupDb()) {
            if ($handle !== 'craft') {
                /** @var Plugin $plugin */
                $plugin = Craft::$app->getPlugins()->getPlugin($handle);
            } else {
                $plugin = null;
            }

            // If this a plugin, make sure it actually has new migrations before backing up the database.
            if ($handle === 'craft' || ($plugin !== null && $plugin->getMigrator()->getNewMigrations())) {
                $return = Craft::$app->getUpdates()->backupDatabase();

                if (!$return['success']) {
                    return $this->asJson([
                        'nextStatus' => Craft::t('app', 'Couldn’t backup the database. How would you like to proceed?'),
                        'junction' => [
                            [
                                'label' => Craft::t('app', 'Cancel the update'),
                                'nextStatus' => Craft::t('app', 'Rolling back…'),
                                'nextAction' => 'update/rollback'
                            ],
                            [
                                'label' => Craft::t('app', 'Continue anyway'),
                                'nextStatus' => Craft::t('app', 'Updating database…'),
                                'nextAction' => 'update/update-database'
                            ],
                        ]
                    ]);
                }

                if (isset($return['dbBackupPath'])) {
                    $data['dbBackupPath'] = Craft::$app->getSecurity()->hashData($return['dbBackupPath']);
                }
            }
        }

        return $this->asJson([
            'nextStatus' => Craft::t('app', 'Updating database…'),
            'nextAction' => 'update/update-database',
            'data' => $data
        ]);
    }

    /**
     * Called during both a manual and auto-update.
     *
     * @return Response
     */
    public function actionUpdateDatabase(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');

        $handle = $this->_getRequestedUpdates($data);

        $return = Craft::$app->getUpdates()->updateDatabase($handle);

        if (!$return['success']) {
            return $this->asJson([
                'errorDetails' => $return['message'],
                'nextStatus' => Craft::t('app', 'An error occurred. Rolling back…'),
                'nextAction' => 'update/rollback'
            ]);
        }

        return $this->asJson([
            'nextStatus' => Craft::t('app', 'Cleaning up…'),
            'nextAction' => 'update/clean-up',
            'data' => $data
        ]);
    }

    /**
     * Performs maintenance and clean up tasks after an update.
     *
     * Called during both a manual and auto-update.
     *
     * @return Response
     * @throws UpdateValidationException
     */
    public function actionCleanUp(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');

        if ($this->_isManualUpdate($data)) {
            $uid = false;
        } else {
            $uid = Craft::$app->getSecurity()->validateData($data['uid']);

            if ($uid === false) {
                throw new UpdateValidationException('Could not validate UID.');
            }
        }

        $handle = $this->_getRequestedUpdates($data);

        $oldVersion = false;

        // Grab the old version from the manifest data before we nuke it.
        $manifestData = Update::getManifestData(Update::getUnzipFolderFromUID($uid), $handle);

        if (!empty($manifestData) && $handle === 'craft') {
            $oldVersion = Update::getLocalVersionFromManifest($manifestData);
        }

        Craft::$app->getUpdates()->updateCleanUp($uid, $handle);

        // New major Craft CMS version?
        if ($handle === 'craft' && $oldVersion !== false && App::majorVersion($oldVersion) < App::majorVersion(Craft::$app->version)) {
            $returnUrl = UrlHelper::url('whats-new');
        } else {
            $returnUrl = Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect();
        }

        return $this->asJson([
            'finished' => true,
            'returnUrl' => $returnUrl
        ]);
    }

    /**
     * Can be called during both a manual and auto-update.
     *
     * @return Response
     * @throws ServerErrorHttpException if reasons
     * @throws UpdateValidationException
     */
    public function actionRollback(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getRequestedUpdates($data);

        if ($this->_isManualUpdate($data)) {
            $uid = false;
        } else {
            $uid = Craft::$app->getSecurity()->validateData($data['uid']);

            if ($uid === false) {
                throw new UpdateValidationException('Could not validate UID.');
            }
        }

        if (isset($data['dbBackupPath'])) {
            $dbBackupPath = Craft::$app->getSecurity()->validateData($data['dbBackupPath']);

            if (!$dbBackupPath) {
                throw new UpdateValidationException('Could not validate database backup path.');
            }

            $return = Craft::$app->getUpdates()->rollbackUpdate($uid, $handle, $dbBackupPath);
        } else {
            $return = Craft::$app->getUpdates()->rollbackUpdate($uid, $handle);
        }

        if (!$return['success']) {
            // Let the JS handle the exception response.
            throw new ServerErrorHttpException($return['message']);
        }

        return $this->asJson([
            'finished' => true,
            'rollBack' => true
        ]);
    }

    /**
     * This method is useful for service like DeployBot, DeployHQ (or any other deployment
     * service that supports a post deployment hook.  You can point them to this controller endpoint
     * and if there are any database migrations that need to run, they will automatically run,
     * minimizing downtime.
     *
     * @throws Exception
     */
    public function actionRunPendingMigrations()
    {
        $this->requirePostRequest();

        $updatesService = Craft::$app->getUpdates();

        $updateCraft = $updatesService->getIsCraftDbMigrationNeeded();
        $updatePlugin = $updatesService->getIsPluginDbUpdateNeeded();

        /** @var Plugin[] $pluginsToUpdate */
        $pluginsToUpdate = [];

        // Make sure either Craft or a plugin needs a migration run.
        if ($updateCraft || $updatePlugin) {
            $pluginsService = Craft::$app->getPlugins();

            if ($updatePlugin) {

                // Figure out which plugins need to update the database.
                $plugins = $pluginsService->getAllPlugins();

                foreach ($plugins as $plugin) {
                    if ($pluginsService->doesPluginRequireDatabaseUpdate($plugin)) {
                        $pluginsToUpdate[] = $plugin;
                    }
                }
            }

            // Run prepare update.
            $return = $updatesService->prepareUpdate(true, 'craft');

            if (!$return['success']) {
                throw new Exception($return['message']);
            }

            $dbBackupPath = false;

            // See if we're allowed to backup the database.
            if ($this->_shouldBackupDb()) {
                // DO it.
                $return = $updatesService->backupDatabase();

                if (!$return['success']) {
                    throw new Exception($return['message']);
                }

                $dbBackupPath = $return['dbBackupPath'];
            }

            // Is there a Craft update?
            if ($updateCraft) {
                $return = $updatesService->updateDatabase('craft');

                if (!$return['success']) {
                    $this->_rollbackUpdate('craft', $return['message'], $dbBackupPath);
                }
            }

            // Run any plugin updates.
            foreach ($pluginsToUpdate as $plugin) {
                $return = $updatesService->updateDatabase($plugin->id);

                if (!$return['success']) {
                    $this->_rollbackUpdate($plugin->id, $return['message'], $dbBackupPath);
                }
            }

            // Cleanup
            $updatesService->updateCleanUp(false, 'craft');
        }
    }

    /**
     * @param string      $handle
     * @param string      $originalErrorMessage
     * @param string|bool $dbBackupPath
     *
     * @throws Exception
     */
    private function _rollbackUpdate(string $handle, string $originalErrorMessage, $dbBackupPath)
    {
        $rollbackReturn = Craft::$app->getUpdates()->rollbackUpdate(false, $handle, $dbBackupPath);

        if (!$rollbackReturn['success']) {
            // It's just not your day, is it?
            throw new Exception($rollbackReturn['message']);
        }

        // We've successfully rolled back, throw the original error message.
        throw new Exception($originalErrorMessage);
    }


    // Private Methods
    // =========================================================================

    /**
     * @param array $data
     *
     * @return bool
     */
    private function _isManualUpdate(array $data): bool
    {
        return isset($data['manualUpdate']) && $data['manualUpdate'] == 1;
    }

    /**
     * Returns the requested updates as handle => version pairs.
     *
     * @param array $data
     *
     * @return array
     * @throws UpdateValidationException
     */
    private function _getRequestedUpdates(array $data): array
    {
        $updates = Craft::$app->getSecurity()->validateData($data['update']);

        if ($updates === false) {
            throw new UpdateValidationException('Could not validate the requested updates.');
        }

        $updates = explode(',', $updates);
        $pairs = [];

        foreach ($updates as $update) {
            list($handle, $version) = explode(':', $update);
            $pairs[$handle] = $version;
        }

        return $pairs;
    }

    /**
     * Returns whether the DB should be backed up, per the config.
     *
     * @return bool
     */
    private function _shouldBackupDb(): bool
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        return ($generalConfig->backupOnUpdate && $generalConfig->backupCommand !== false);
    }

    /**
     * Returns the response to initiate the first "update database" step (either backup or update).
     *
     * @param array $data
     *
     * @return Response
     */
    private function _getFirstDbUpdateResponse(array $data): Response
    {
        if ($this->_shouldBackupDb()) {
            $response = [
                'nextStatus' => Craft::t('app', 'Backing-up database…'),
                'nextAction' => 'update/backup-database',
                'data' => $data
            ];
        } else {
            $response = [
                'nextStatus' => Craft::t('app', 'Updating database…'),
                'nextAction' => 'update/update-database',
                'data' => $data
            ];
        }

        return $this->asJson($response);
    }

    /**
     * Returns whether the system is allowed to be auto-updated to the latest Craft release.
     *
     * @return bool Whether the system is allowed to be auto-updated to the latest release.
     */
    private function _allowAutoUpdates(): bool
    {
        return true;

        $update = Craft::$app->getUpdates()->getUpdates();

        if (!$update) {
            return false;
        }

        $configVal = Craft::$app->getConfig()->getGeneral()->allowAutoUpdates;

        if (is_bool($configVal)) {
            return $configVal;
        }

        if ($configVal === GeneralConfig::AUTO_UPDATE_PATCH_ONLY) {
            // Return true if the major and minor versions are still the same
            return (App::majorMinorVersion($update->app->latestVersion) === App::majorMinorVersion(Craft::$app->version));
        }

        if ($configVal === GeneralConfig::AUTO_UPDATE_MINOR_ONLY) {
            // Return true if the major version is still the same
            return (App::majorVersion($update->app->latestVersion) === App::majorVersion(Craft::$app->version));
        }

        return false;
    }
}
