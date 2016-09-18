<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Plugin;
use craft\app\base\PluginInterface;
use craft\app\enums\PluginUpdateStatus;
use craft\app\enums\VersionUpdateStatus;
use craft\app\events\UpdateEvent;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\Io;
use craft\app\helpers\Json;
use craft\app\helpers\Update as UpdateHelper;
use craft\app\i18n\Locale;
use craft\app\models\AppUpdate;
use craft\app\models\Et;
use craft\app\models\PluginNewRelease;
use craft\app\models\PluginUpdate;
use craft\app\models\Update;
use craft\app\updates\Updater;
use GuzzleHttp\Client;
use yii\base\Component;
use yii\base\Exception;
use yii\base\UserException;
use yii\helpers\Markdown;

/**
 * Class Updates service.
 *
 * An instance of the Updates service is globally accessible in Craft via [[Application::updates `Craft::$app->getUpdates()`]].
 *
 * @property boolean $hasCraftBuildChanged      Whether a different Craft build has been uploaded
 * @property boolean $isBreakpointUpdateNeeded  Whether the build stored in craft_info is less than the minimum required build on the file system
 * @property boolean $isCraftDbMigrationNeeded  Whether Craft needs to run any database migrations
 * @property boolean $isCriticalUpdateAvailable Whether a critical update is available
 * @property boolean $isManualUpdateRequired    Whether a manual update is required
 * @property boolean $isPluginDbUpdateNeeded    Whether a plugin needs to run a database update
 * @property boolean $isSchemaVersionCompatible Whether the uploaded DB schema is equal to or greater than the installed schema
 * @property boolean $isUpdateInfoCached        Whether the update info is cached
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Updates extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event UpdateEvent The event that is triggered before an update is installed.
     */
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';

    /**
     * @event UpdateEvent The event that is triggered after an update is installed.
     */
    const EVENT_AFTER_UPDATE = 'afterUpdate';

    /**
     * @event UpdateEvent The event that is triggered after an update has failed to install.
     */
    const EVENT_UPDATE_FAILURE = 'updateFailure';

    // Properties
    // =========================================================================

    /**
     * @var Update
     */
    private $_updateModel;

    /**
     * @var boolean
     */
    private $_isCraftDbMigrationNeeded;

    // Public Methods
    // =========================================================================

    /**
     * @param $craftReleases
     *
     * @return boolean
     */
    public function criticalCraftUpdateAvailable($craftReleases)
    {
        foreach ($craftReleases as $craftRelease) {
            if ($craftRelease->critical) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $plugins
     *
     * @return boolean
     */
    public function criticalPluginUpdateAvailable($plugins)
    {
        foreach ($plugins as $plugin) {
            if ($plugin->status == PluginUpdateStatus::UpdateAvailable && count($plugin->releases) > 0) {
                foreach ($plugin->releases as $release) {
                    if ($release->critical) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns whether the update info is cached.
     *
     * @return boolean
     */
    public function getIsUpdateInfoCached()
    {
        return (isset($this->_updateModel) || Craft::$app->getCache()->get('updateinfo') !== false);
    }

    /**
     * @return integer
     */
    public function getTotalAvailableUpdates()
    {
        $count = 0;

        if ($this->getIsUpdateInfoCached()) {
            $updateModel = $this->getUpdates();

            // Could be false!
            if ($updateModel) {
                if (!empty($updateModel->app)) {
                    if ($updateModel->app->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable) {
                        if (isset($updateModel->app->releases) && count($updateModel->app->releases) > 0) {
                            $count++;
                        }
                    }
                }

                if (!empty($updateModel->plugins)) {
                    foreach ($updateModel->plugins as $plugin) {
                        if ($plugin->status == PluginUpdateStatus::UpdateAvailable) {
                            if (isset($plugin->releases) && count($plugin->releases) > 0) {
                                $count++;
                            }
                        }
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Returns whether a critical update is available.
     *
     * @return boolean
     */
    public function getIsCriticalUpdateAvailable()
    {
        if (!empty($this->_updateModel->app->criticalUpdateAvailable)) {
            return true;
        }

        foreach ($this->_updateModel->plugins as $pluginUpdateModel) {
            if ($pluginUpdateModel->criticalUpdateAvailable) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether a manual update is required.
     *
     * @return mixed
     */
    public function getIsManualUpdateRequired()
    {
        return (!empty($this->_updateModel->app->manualUpdateRequired));
    }

    /**
     * @param boolean $forceRefresh
     *
     * @return Update|false
     */
    public function getUpdates($forceRefresh = false)
    {
        if (!isset($this->_updateModel) || $forceRefresh) {
            $updateModel = false;

            if (!$forceRefresh) {
                // get the update info from the cache if it's there
                $updateModel = Craft::$app->getCache()->get('updateinfo');
            }

            // fetch it if it wasn't cached, or if we're forcing a refresh
            if ($forceRefresh || $updateModel === false) {
                $etModel = $this->check();

                if ($etModel == null) {
                    $updateModel = new Update();
                    $errors[] = Craft::t('app', 'Craft is unable to determine if an update is available at this time.');
                    $updateModel->errors = $errors;
                } else {
                    /** @var Update $updateModel */
                    $updateModel = $etModel->data;

                    // Search for any missing plugin updates based on their feeds
                    $this->checkPluginReleaseFeeds($updateModel);

                    // cache it and set it to expire according to config
                    Craft::$app->getCache()->set('updateinfo', $updateModel);
                }
            }

            $this->_updateModel = $updateModel;
        }

        return $this->_updateModel;
    }

    /**
     * @return boolean
     */
    public function flushUpdateInfoFromCache()
    {
        Craft::info('Flushing update info from cache.', __METHOD__);

        if (Io::clearFolder(Craft::$app->getPath()->getCompiledTemplatesPath(),
                true) && Craft::$app->getCache()->flush()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param PluginInterface $plugin
     *
     * @return boolean
     */
    public function setNewPluginInfo(PluginInterface $plugin)
    {
        /** @var Plugin $plugin */
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                [
                    'version' => $plugin->version,
                    'schemaVersion' => $plugin->schemaVersion
                ],
                ['handle' => $plugin->getHandle()])
            ->execute();

        $success = (bool)$affectedRows;

        return $success;
    }

    /**
     * @return Et
     */
    public function check()
    {
        Craft::$app->getConfig()->maxPowerCaptain();

        $updateModel = new Update();
        $updateModel->app = new AppUpdate();
        $updateModel->app->localVersion = Craft::$app->version;
        $updateModel->app->localBuild = Craft::$app->build;

        /** @var Plugin[] $plugins */
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        $pluginUpdateModels = [];

        foreach ($plugins as $plugin) {
            $pluginUpdateModel = new PluginUpdate();
            $pluginUpdateModel->class = $plugin->getHandle();
            $pluginUpdateModel->localVersion = $plugin->version;

            $pluginUpdateModels[$plugin::className()] = $pluginUpdateModel;
        }

        $updateModel->plugins = $pluginUpdateModels;

        $etModel = Craft::$app->getEt()->checkForUpdates($updateModel);

        return $etModel;
    }

    /**
     * Check plugins’ release feeds and include any pending updates in the given Update
     *
     * @param Update $updateModel
     *
     * @return void
     */
    public function checkPluginReleaseFeeds(Update $updateModel)
    {
        $userAgent = 'Craft/'.Craft::$app->version.'.'.Craft::$app->build;

        foreach ($updateModel->plugins as $pluginUpdateModel) {
            // Only check plugins where the update status isn't already known from the ET response
            if ($pluginUpdateModel->status != PluginUpdateStatus::Unknown) {
                continue;
            }

            // Get the plugin and its feed URL
            /** @var Plugin $plugin */
            $plugin = Craft::$app->getPlugins()->getPlugin($pluginUpdateModel->class);

            // Skip if the plugin doesn't have a feed URL
            if ($plugin->releaseFeedUrl === null) {
                continue;
            }

            // Make sure it's HTTPS
            if (strncmp($plugin->releaseFeedUrl, 'https://', 8) !== 0) {
                Craft::warning('The “'.$plugin->name.'” plugin has a release feed URL, but it doesn’t begin with https://, so it’s getting skipped ('.$plugin->releaseFeedUrl.').');
                continue;
            }

            try {
                // Fetch it
                $client = new Client([
                    'headers' => [
                        'User-Agent' => $userAgent,
                    ],
                    'timeout' => 5,
                    'connect_timeout' => 2,
                    'allow_redirects' => true,
                    'verify' => false
                ]);

                // Potentially long-running request, so close session to prevent session blocking on subsequent requests.
                Craft::$app->getSession()->close();

                $response = $client->get($plugin->releaseFeedUrl, null);

                if ($response->getStatusCode() != 200) {
                    Craft::warning('Error in calling '.$plugin->releaseFeedUrl.'. Response: '.$response->getBody());
                    continue;
                }

                $responseBody = $response->getBody();
                $releases = Json::decode($responseBody);

                if (!$releases) {
                    Craft::warning('The “'.$plugin->name."” plugin release feed didn’t come back as valid JSON:\n".$responseBody);
                    continue;
                }

                $releaseModels = [];
                $releaseTimestamps = [];

                foreach ($releases as $release) {
                    // Validate ite info
                    $errors = [];

                    // Any missing required attributes?
                    $missingAttributes = [];

                    foreach ([
                                 'version',
                                 'downloadUrl',
                                 'date',
                                 'notes'
                             ] as $attribute) {
                        if (empty($release[$attribute])) {
                            $missingAttributes[] = $attribute;
                        }
                    }

                    if ($missingAttributes) {
                        $errors[] = 'Missing required attributes ('.implode(', ', $missingAttributes).')';
                    }

                    // downloadUrl could be missing.
                    if (!empty($release['downloadUrl'])) {
                        // Invalid URL?
                        if (strncmp($release['downloadUrl'], 'https://', 8) !== 0) {
                            $errors[] = 'Download URL doesn’t begin with https:// ('.$release['downloadUrl'].')';
                        }
                    }

                    // release date could be missing.
                    if (!empty($release['date'])) {
                        // Invalid date?
                        $date = DateTimeHelper::toDateTime($release['date']);
                        if (!$date) {
                            $errors[] = 'Invalid date ('.$release['date'].')';
                        }
                    }

                    // Validation complete. Were there any errors?
                    if ($errors) {
                        Craft::warning('A “'.$plugin->name."” release was skipped because it is invalid:\n - ".implode("\n - ", $errors));
                        continue;
                    }

                    // All good! Let's make sure it's a pending update
                    if (!version_compare($release['version'], $plugin->version, '>')) {
                        continue;
                    }

                    // Create the release note HTML
                    if (!is_array($release['notes'])) {
                        $release['notes'] = array_filter(preg_split('/[\r\n]+/', $release['notes']));
                    }

                    $notes = '';
                    $inList = false;

                    foreach ($release['notes'] as $line) {
                        // Escape any HTML
                        $line = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                        // Is this a heading?
                        if (preg_match('/^#\s+(.+)/', $line, $match)) {
                            if ($inList) {
                                $notes .= "</ul>\n";
                                $inList = false;
                            }

                            $notes .= '<h3>'.$match[1]."</h3>\n";
                        } else {
                            if (!$inList) {
                                $notes .= "<ul>\n";
                                $inList = true;
                            }

                            if (preg_match('/^\[(\w+)\]\s+(.+)/', $line, $match)) {
                                $class = strtolower($match[1]);
                                $line = $match[2];
                            } else {
                                $class = null;
                            }

                            // Parse Markdown code
                            $line = Markdown::processParagraph($line);

                            $notes .= '<li'.($class ? ' class="'.$class.'"' : '').'>'.$line."</li>\n";
                        }
                    }

                    if ($inList) {
                        $notes .= "</ul>\n";
                    }

                    $critical = !empty($release['critical']);

                    if (!isset($date)) {
                        $date = new \DateTime();
                    }

                    // Populate the release model
                    $releaseModel = new PluginNewRelease();
                    $releaseModel->version = $release['version'];
                    $releaseModel->date = $date;
                    $releaseModel->localizedDate = Craft::$app->getFormatter()->asDate($date, Locale::LENGTH_SHORT);
                    $releaseModel->notes = $notes;
                    $releaseModel->critical = $critical;
                    $releaseModel->manualDownloadEndpoint = $release['downloadUrl'];

                    $releaseModels[] = $releaseModel;
                    $releaseTimestamps[] = $date->getTimestamp();

                    if ($critical) {
                        $pluginUpdateModel->criticalUpdateAvailable = true;
                    }
                }

                if ($releaseModels) {
                    // Sort release models by timestamp
                    array_multisort($releaseTimestamps, SORT_DESC, $releaseModels);
                    $latestRelease = $releaseModels[0];

                    $pluginUpdateModel->displayName = $plugin->name;
                    $pluginUpdateModel->localVersion = $plugin->version;
                    $pluginUpdateModel->latestDate = $latestRelease->date;
                    $pluginUpdateModel->latestVersion = $latestRelease->version;
                    $pluginUpdateModel->manualDownloadEndpoint = $latestRelease->manualDownloadEndpoint;
                    $pluginUpdateModel->manualUpdateRequired = true;
                    $pluginUpdateModel->releases = $releaseModels;
                    $pluginUpdateModel->status = PluginUpdateStatus::UpdateAvailable;
                } else {
                    $pluginUpdateModel->status = PluginUpdateStatus::UpToDate;
                }
            } catch (\Exception $e) {
                Craft::error('There was a problem getting the update feed for “'.$plugin->name.'”, so it was skipped: '.$e->getMessage());
                continue;
            }
        }
    }

    /**
     * Checks to see if Craft can write to a defined set of folders/files that are
     * needed for auto-update to work.
     *
     * @return array|null
     */
    public function getUnwritableFolders()
    {
        $checkPaths = [
            Craft::$app->getPath()->getAppPath(),
            Craft::$app->getPath()->getPluginsPath(),
        ];

        $errorPath = null;

        foreach ($checkPaths as $writablePath) {
            if (!Io::isWritable($writablePath)) {
                $errorPath[] = Io::getRealPath($writablePath);
            }
        }

        return $errorPath;
    }

    /**
     * @param $manual
     * @param $handle
     *
     * @return array
     */
    public function prepareUpdate($manual, $handle)
    {
        Craft::info('Preparing to update '.$handle.'.', __METHOD__);

        // Fire a 'beforeUpdate' event
        $this->trigger(self::EVENT_BEFORE_UPDATE, new UpdateEvent([
            'type' => $manual ? 'manual' : 'auto',
            'handle' => $handle,
        ]));

        try {
            $updater = new Updater();

            // Make sure we still meet the existing requirements. This will throw an exception if the server doesn't meet Craft's current requirements.
            Craft::$app->runAction('templates/requirements-check');

            // No need to get the latest update info if this is a manual update.
            if (!$manual) {
                $updateModel = $this->getUpdates();

                if ($handle == 'craft') {
                    Craft::info('Updating from '.$updateModel->app->localVersion.'.'.$updateModel->app->localBuild.' to '.$updateModel->app->latestVersion.'.'.$updateModel->app->latestBuild.'.');
                } else {
                    $latestVersion = null;
                    $localVersion = null;
                    $handle = null;

                    foreach ($updateModel->plugins as $pluginUpdateModel) {
                        if (strtolower($pluginUpdateModel->class) === $handle) {
                            $latestVersion = $pluginUpdateModel->latestVersion;
                            $localVersion = $pluginUpdateModel->localVersion;
                            $handle = $pluginUpdateModel->class;

                            break;
                        }
                    }

                    Craft::info('Updating plugin "'.$handle.'" from '.$localVersion.' to '.$latestVersion.'.');
                }

                $result = $updater->getUpdateFileInfo($handle);
            }

            $result['success'] = true;

            Craft::info('Finished preparing to update '.$handle.'.', __METHOD__);

            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param string $md5
     * @param string $handle
     *
     * @return array
     */
    public function processUpdateDownload($md5, $handle)
    {
        Craft::info('Starting to process the update download.', __METHOD__);

        try {
            $updater = new Updater();
            $result = $updater->processDownload($md5, $handle);
            $result['success'] = true;

            Craft::info('Finished processing the update download.', __METHOD__);

            return $result;
        } catch (UserException $e) {
            Craft::error('Error processing the update download: '.$e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            Craft::error('Error processing the update download: '.$e->getMessage());
            return [
                'success' => false,
                'message' => Craft::t('app', 'There was a problem during the update.')
            ];
        }
    }

    /**
     * @param string $uid
     * @param string $handle
     *
     * @return array
     */
    public function backupFiles($uid, $handle)
    {
        Craft::info('Starting to backup files that need to be updated.', __METHOD__);

        try {
            $updater = new Updater();
            $updater->backupFiles($uid, $handle);

            Craft::info('Finished backing up files.');

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param string $uid
     * @param string $handle
     *
     * @return array
     */
    public function updateFiles($uid, $handle)
    {
        Craft::info('Starting to update files.', __METHOD__);

        try {
            $updater = new Updater();
            $updater->updateFiles($uid, $handle);

            Craft::info('Finished updating files.', __METHOD__);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array
     */
    public function backupDatabase()
    {
        Craft::info('Starting to backup database.', __METHOD__);

        try {
            $updater = new Updater();
            $result = $updater->backupDatabase();

            if (!$result) {
                Craft::info('Did not backup database because there were no migrations to run.', __METHOD__);

                return ['success' => true];
            }

            Craft::info('Finished backing up database.', __METHOD__);

            return ['success' => true, 'dbBackupPath' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param string $handle
     *
     * @throws Exception
     * @return array
     */
    public function updateDatabase($handle)
    {
        Craft::info('Starting to update the database.', __METHOD__);

        try {
            $updater = new Updater();

            if ($handle == 'craft') {
                Craft::info('Craft wants to update the database.', __METHOD__);
                $updater->updateDatabase();
                Craft::info('Craft is done updating the database.', __METHOD__);
            } else {
                // Make sure plugins are loaded.
                Craft::$app->getPlugins()->loadPlugins();

                $plugin = Craft::$app->getPlugins()->getPlugin($handle);

                if ($plugin) {
                    /** @var Plugin $plugin */
                    Craft::info('The plugin, '.$plugin->name.' wants to update the database.', __METHOD__);
                    $updater->updateDatabase($plugin);
                    Craft::info('The plugin, '.$plugin->name.' is done updating the database.', __METHOD__);
                } else {
                    Craft::error('Cannot find a plugin with the handle '.$handle.' or it is not enabled, therefore it cannot update the database.', __METHOD__);
                    throw new Exception("Cannot find an enabled plugin with the handle '{$handle}'");
                }
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param string $uid
     * @param string $handle
     *
     * @return array
     */
    public function updateCleanUp($uid, $handle)
    {
        Craft::info('Starting to clean up after the update.', __METHOD__);

        try {
            $updater = new Updater();
            $updater->cleanUp($uid, $handle);

            Craft::info('Finished cleaning up after the update.', __METHOD__);
        } catch (\Exception $e) {
            Craft::info('There was an error during cleanup, but we don\'t really care: '.$e->getMessage(), __METHOD__);
        }

        // Fire an 'afterUpdate' event
        $this->trigger(self::EVENT_AFTER_UPDATE, new UpdateEvent([
            'handle' => $handle,
        ]));
    }

    /**
     * @param string  $uid
     * @param string  $handle
     * @param boolean $dbBackupPath
     *
     * @return array
     */
    public function rollbackUpdate($uid, $handle, $dbBackupPath = false)
    {
        try {
            // Fire an 'updateFailure' event
            $this->trigger(self::EVENT_UPDATE_FAILURE, new UpdateEvent([
                'handle' => $handle,
            ]));

            Craft::$app->getConfig()->maxPowerCaptain();

            if ($dbBackupPath && Craft::$app->getConfig()->get('backupDbOnUpdate') && Craft::$app->getConfig()->get('restoreDbOnUpdateFailure')) {
                Craft::info('Rolling back any database changes.', __METHOD__);
                UpdateHelper::rollBackDatabaseChanges($dbBackupPath);
                Craft::info('Done rolling back any database changes.', __METHOD__);
            }

            // If uid !== false, it's an auto-update.
            if ($uid !== false) {
                Craft::info('Rolling back any file changes.', __METHOD__);
                $manifestData = UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid), $handle);

                if ($manifestData) {
                    UpdateHelper::rollBackFileChanges($manifestData, $handle);
                }

                Craft::info('Done rolling back any file changes.', __METHOD__);
            }

            Craft::info('Finished rolling back changes.', __METHOD__);

            Craft::info('Taking the site out of maintenance mode.', __METHOD__);
            Craft::$app->disableMaintenanceMode();

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Returns whether a plugin needs to run a database update.
     *
     * @return boolean
     */
    public function getIsPluginDbUpdateNeeded()
    {
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($plugins as $plugin) {
            if (Craft::$app->getPlugins()->doesPluginRequireDatabaseUpdate($plugin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether a different Craft build has been uploaded.
     *
     * @return boolean
     */
    public function getHasCraftBuildChanged()
    {
        $storedBuild = Craft::$app->getInfo('build');

        return (Craft::$app->build != $storedBuild);
    }

    /**
     * Returns whether the build stored in craft_info is less than the minimum required build on the file system.
     *
     * This effectively makes sure that a user cannot manually update past a manual breakpoint.
     *
     * @return boolean
     */
    public function getIsBreakpointUpdateNeeded()
    {
        $storedBuild = Craft::$app->getInfo('build');

        return (Craft::$app->minBuildRequired > $storedBuild);
    }

    /**
     * Returns whether the uploaded DB schema is equal to or greater than the installed schema.
     *
     * @return boolean
     */
    public function getIsSchemaVersionCompatible()
    {
        $storedSchemaVersion = Craft::$app->getInfo('schemaVersion');

        return version_compare(Craft::$app->schemaVersion, $storedSchemaVersion, '>=');
    }

    /**
     * Returns whether Craft needs to run any database migrations.
     *
     * @return boolean
     */
    public function getIsCraftDbMigrationNeeded()
    {
        if ($this->_isCraftDbMigrationNeeded === null) {
            $storedSchemaVersion = Craft::$app->getInfo('schemaVersion');
            $this->_isCraftDbMigrationNeeded = version_compare(Craft::$app->schemaVersion, $storedSchemaVersion, '>');
        }

        return $this->_isCraftDbMigrationNeeded;
    }

    /**
     * Updates the Craft version info in the craft_info table.
     *
     * @return boolean
     */
    public function updateCraftVersionInfo()
    {
        $info = Craft::$app->getInfo();
        $info->version = Craft::$app->version;
        $info->build = Craft::$app->build;
        $info->schemaVersion = Craft::$app->schemaVersion;
        $info->track = Craft::$app->track;
        $info->releaseDate = Craft::$app->releaseDate;

        return Craft::$app->saveInfo($info);
    }

    /**
     * Returns a list of plugins that are in need of a database update.
     *
     * @return PluginInterface[]|null
     */
    public function getPluginsThatNeedDbUpdate()
    {
        $pluginsThatNeedDbUpdate = [];

        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($plugins as $plugin) {
            if (Craft::$app->getPlugins()->doesPluginRequireDatabaseUpdate($plugin)) {
                $pluginsThatNeedDbUpdate[] = $plugin;
            }
        }

        return $pluginsThatNeedDbUpdate;
    }
}
