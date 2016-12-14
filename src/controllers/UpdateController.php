<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\base\Plugin;
use craft\enums\PluginUpdateStatus;
use craft\errors\EtException;
use craft\errors\UpdateValidationException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\Update;
use craft\helpers\Url;
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
 * via [[Controller::allowAnonymous]].
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
     * @return string
     */
    public function actionIndex()
    {
        $view = $this->getView();
        $view->registerCssResource('css/updates.css');
        $view->registerJsResource('js/UpdatesPage.js');
        $view->registerTranslations('app', [
            'You’ve got updates!',
            'You’re all up-to-date!',
            'Critical',
            'Update',
            'Download',
            'Craft’s <a href="http://craftcms.com/license" target="_blank">Terms and Conditions</a> have changed.',
            'I agree.',
            'Seriously, download.',
            'Seriously, update.',
            'Install',
            '{app} update required',
            'Released on {date}',
            'Show more',
            'Added',
            'Improved',
            'Fixed',
            'Download',
            'Use Composer to get this update.',
        ]);

        $isComposerInstallJs = Json::encode(App::isComposerInstall());
        $js = <<<JS
//noinspection JSUnresolvedVariable
new Craft.UpdatesPage({
    isComposerInstall: {$isComposerInstallJs}
});
JS;
        $view->registerJs($js);

        return $this->renderTemplate('_special/updates/index');
    }

    /**
     * Update kickoff
     *
     * @param string $handle The update handle ("craft" or a plugin handle)
     *
     * @return string
     */
    public function actionGo($handle)
    {
        $this->getView()->registerCssResource('css/update.css');
        $this->getView()->registerJsResource('js/Updater.js');

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

        $dataJs = Json::encode([
            'handle' => Craft::$app->getSecurity()->hashData($handle),
            'manualUpdate' => (Craft::$app->getRequest()->getSegment(1) == 'manualupdate') ? 1 : 0
        ]);
        $js = <<<JS
//noinspection JSUnresolvedVariable
new Craft.Updater({$dataJs});
JS;

        $this->getView()->registerJs($js);

        return $this->renderTemplate('_special/updates/go');
    }

    // Auto Updates
    // -------------------------------------------------------------------------

    /**
     * Returns the available updates.
     *
     * @return Response
     */
    public function actionGetAvailableUpdates()
    {
        $this->requirePermission('performUpdates');

        try {
            $updates = Craft::$app->getUpdates()->getUpdates(true);
        } catch (EtException $e) {
            $updates = false;

            if ($e->getCode() == 10001) {
                return $this->asErrorJson($e->getMessage());
            }
        }

        if ($updates) {
            $response = $updates->toArray();

            // responseErrors => errors
            if (array_key_exists('responseErrors', $response)) {
                $response['errors'] = $response['responseErrors'];
                unset($response['responseErrors']);
            }

            $response['allowAutoUpdates'] = Craft::$app->getConfig()->allowAutoUpdates();

            return $this->asJson($response);
        }

        return $this->asErrorJson(Craft::t('app', 'Could not fetch available updates at this time.'));
    }

    /**
     * Returns the update info JSON.
     *
     * @return Response
     */
    public function actionGetUpdates()
    {
        $this->requirePermission('performUpdates');

        $this->requireAcceptsJson();

        $handle = Craft::$app->getRequest()->getRequiredBodyParam('handle');

        $return = [];
        $updateInfo = Craft::$app->getUpdates()->getUpdates();

        if (!$updateInfo) {
            return $this->asErrorJson(Craft::t('app', 'There was a problem getting the latest update information.'));
        }

        try {
            if ($handle == 'all' || $handle == 'craft') {
                $return[] = [
                    'handle' => 'craft',
                    'name' => 'Craft',
                    'version' => $updateInfo->app->latestVersion,
                    'critical' => $updateInfo->app->criticalUpdateAvailable,
                    'releaseDate' => $updateInfo->app->latestDate->getTimestamp()
                ];
            }

            if ($handle != 'craft') {
                foreach ($updateInfo->plugins as $plugin) {
                    if ($handle != 'all' && $handle != $plugin->class) {
                        continue;
                    }

                    if ($plugin->status == PluginUpdateStatus::UpdateAvailable && count($plugin->releases) > 0) {
                        $return[] = [
                            'handle' => $plugin->class,
                            'name' => $plugin->displayName,
                            'version' => $plugin->latestVersion,
                            'critical' => $plugin->criticalUpdateAvailable,
                            'releaseDate' => $plugin->latestDate->getTimestamp()
                        ];
                    }
                }
            }

            return $this->asJson(['success' => true, 'updateInfo' => $return]);
        } catch (\Exception $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    /**
     * Called during both a manual and auto-update.
     *
     * @return Response
     */
    public function actionPrepare()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getFixedHandle($data);

        $manual = false;
        if (!$this->_isManualUpdate($data)) {
            // If it's not a manual update, make sure they have auto-update permissions.
            $this->requirePermission('performUpdates');

            if (!Craft::$app->getConfig()->allowAutoUpdates()) {
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
    public function actionProcessDownload()
    {
        // This method should never be called in a manual update.
        $this->requirePermission('performUpdates');

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (!Craft::$app->getConfig()->allowAutoUpdates()) {
            return $this->asJson([
                'errorDetails' => Craft::t('app', 'Auto-updating is disabled on this system.'),
                'finished' => true
            ]);
        }

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getFixedHandle($data);

        $md5 = Craft::$app->getSecurity()->validateData($data['md5']);

        if (!$md5) {
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
    public function actionBackupFiles()
    {
        // This method should never be called in a manual update.
        $this->requirePermission('performUpdates');

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (!Craft::$app->getConfig()->allowAutoUpdates()) {
            return $this->asJson([
                'errorDetails' => Craft::t('app', 'Auto-updating is disabled on this system.'),
                'finished' => true
            ]);
        }

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getFixedHandle($data);

        $uid = Craft::$app->getSecurity()->validateData($data['uid']);

        if (!$uid) {
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
    public function actionUpdateFiles()
    {
        // This method should never be called in a manual update.
        $this->requirePermission('performUpdates');

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (!Craft::$app->getConfig()->allowAutoUpdates()) {
            return $this->asJson([
                'errorDetails' => Craft::t('app', 'Auto-updating is disabled on this system.'),
                'finished' => true
            ]);
        }

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getFixedHandle($data);

        $uid = Craft::$app->getSecurity()->validateData($data['uid']);

        if (!$uid) {
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
    public function actionBackupDatabase()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getFixedHandle($data);

        if (true || $this->_shouldBackupDb()) {
            if ($handle !== 'craft') {
                /** @var Plugin $plugin */
                $plugin = Craft::$app->getPlugins()->getPlugin($handle);
            }

            // If this a plugin, make sure it actually has new migrations before backing up the database.
            if ($handle === 'craft' || (!empty($plugin) && $plugin->getMigrator()->getNewMigrations())) {
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
    public function actionUpdateDatabase()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');

        $handle = $this->_getFixedHandle($data);

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
    public function actionCleanUp()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');

        if ($this->_isManualUpdate($data)) {
            $uid = false;
        } else {
            $uid = Craft::$app->getSecurity()->validateData($data['uid']);

            if (!$uid) {
                throw new UpdateValidationException('Could not validate UID.');
            }
        }

        $handle = $this->_getFixedHandle($data);

        $oldVersion = false;

        // Grab the old version from the manifest data before we nuke it.
        $manifestData = Update::getManifestData(Update::getUnzipFolderFromUID($uid), $handle);

        if ($manifestData && $handle == 'craft') {
            $oldVersion = Update::getLocalVersionFromManifest($manifestData);
        }

        Craft::$app->getUpdates()->updateCleanUp($uid, $handle);

        // New major Craft CMS version?
        if ($handle == 'craft' && $oldVersion && App::majorVersion($oldVersion) < App::majorVersion(Craft::$app->version)) {
            $returnUrl = Url::url('whats-new');
        } else {
            $returnUrl = Craft::$app->getConfig()->get('postCpLoginRedirect');
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
    public function actionRollback()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->getRequest()->getRequiredBodyParam('data');
        $handle = $this->_getFixedHandle($data);

        if ($this->_isManualUpdate($data)) {
            $uid = false;
        } else {
            $uid = Craft::$app->getSecurity()->validateData($data['uid']);

            if (!$uid) {
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

            $config = Craft::$app->getConfig();

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

                $return = $updatesService->updateDatabase($plugin->getHandle());

                if (!$return['success']) {
                    $this->_rollbackUpdate($plugin->getHandle(), $return['message'], $dbBackupPath);
                }
            }

            // Cleanup
            $updatesService->updateCleanUp(false, 'craft');
        }
    }

    /**
     * @param $handle
     * @param $originalErrorMessage
     *
     * @param $dbBackupPath
     *
     * @throws Exception
     */
    private function _rollbackUpdate($handle, $originalErrorMessage, $dbBackupPath)
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
     * @param $data
     *
     * @return boolean
     */
    private function _isManualUpdate($data)
    {
        return isset($data['manualUpdate']) && $data['manualUpdate'] == 1;
    }

    /**
     * @param $data
     *
     * @return string
     * @throws UpdateValidationException
     */
    private function _getFixedHandle($data)
    {
        if (!isset($data['handle'])) {
            return 'craft';
        } else {
            if ($handle = Craft::$app->getSecurity()->validateData($data['handle'])) {
                return $handle;
            }
        }

        throw new UpdateValidationException('Could not validate the update handle.');
    }

    /**
     * Returns whether the DB should be backed up, per the config.
     *
     * @return boolean
     */
    private function _shouldBackupDb()
    {
        $config = Craft::$app->getConfig();

        return ($config->get('backupOnUpdate') && $config->get('backupCommand') !== false);
    }

    /**
     * Returns the response to initiate the first "update database" step (either backup or update).
     *
     * @param array $data
     *
     * @return Response
     */
    private function _getFirstDbUpdateResponse($data)
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
}
