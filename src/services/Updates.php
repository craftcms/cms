<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Plugin;
use craft\base\PluginInterface;
use craft\enums\PluginUpdateStatus;
use craft\enums\VersionUpdateStatus;
use craft\errors\InvalidPluginException;
use craft\events\UpdateEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\Update as UpdateHelper;
use craft\i18n\Locale;
use craft\models\AppUpdate;
use craft\models\Et;
use craft\models\PluginNewRelease;
use craft\models\PluginUpdate;
use craft\models\Update;
use craft\updates\Updater;
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
 * @property bool $hasCraftBuildChanged      Whether a different Craft build has been uploaded
 * @property bool $isBreakpointUpdateNeeded  Whether the build stored in craft_info is less than the minimum required build on the file system
 * @property bool $isCraftDbMigrationNeeded  Whether Craft needs to run any database migrations
 * @property bool $isCriticalUpdateAvailable Whether a critical update is available
 * @property bool $isManualUpdateRequired    Whether a manual update is required
 * @property bool $isPluginDbUpdateNeeded    Whether a plugin needs to run a database update
 * @property bool $isSchemaVersionCompatible Whether the uploaded DB schema is equal to or greater than the installed schema
 * @property bool $isUpdateInfoCached        Whether the update info is cached
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
     * @var bool
     */
    private $_isCraftDbMigrationNeeded;

    // Public Methods
    // =========================================================================

    /**
     * @param $craftReleases
     *
     * @return bool
     */
    public function criticalCraftUpdateAvailable($craftReleases): bool
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
     * @return bool
     */
    public function criticalPluginUpdateAvailable($plugins): bool
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
     * @return bool
     */
    public function getIsUpdateInfoCached(): bool
    {
        return ($this->_updateModel !== null || Craft::$app->getCache()->get('updateinfo') !== false);
    }

    /**
     * @return int
     */
    public function getTotalAvailableUpdates(): int
    {
        if (!$this->getIsUpdateInfoCached()) {
            return 0;
        }

        if (($update = $this->getUpdates()) === false) {
            return 0;
        }

        $count = 0;

        if ($update->app !== null && $update->app->versionUpdateStatus === VersionUpdateStatus::UpdateAvailable) {
            $count++;
        }

        if (!empty($update->plugins)) {
            foreach ($update->plugins as $pluginUpdate) {
                if ($pluginUpdate->status === PluginUpdateStatus::UpdateAvailable) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Returns whether a critical update is available.
     *
     * @return bool
     */
    public function getIsCriticalUpdateAvailable(): bool
    {
        if (!empty($this->_updateModel->app->criticalUpdateAvailable)) {
            return true;
        }

        foreach ($this->_updateModel->plugins as $pluginUpdate) {
            if ($pluginUpdate->criticalUpdateAvailable) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether a manual update is required.
     *
     * @return bool
     */
    public function getIsManualUpdateRequired(): bool
    {
        return (!empty($this->_updateModel->app->manualUpdateRequired));
    }

    /**
     * @param bool $forceRefresh
     *
     * @return Update|false
     */
    public function getUpdates(bool $forceRefresh = false)
    {
        if ($this->_updateModel !== null && !$forceRefresh) {
            return $this->_updateModel;
        }

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
                $updateModel->responseErrors = [
                    Craft::t('app', 'Craft is unable to determine if an update is available at this time.')
                ];
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

        return $this->_updateModel;
    }

    /**
     * @param PluginInterface $plugin
     *
     * @return bool
     */
    public function setNewPluginInfo(PluginInterface $plugin): bool
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

        return (bool)$affectedRows;
    }

    /**
     * @return Et|null
     */
    public function check()
    {
        Craft::$app->getConfig()->maxPowerCaptain();

        $updateModel = new Update();
        $updateModel->app = new AppUpdate();
        $updateModel->app->localVersion = Craft::$app->version;

        /** @var Plugin[] $plugins */
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        $updateModel->plugins = [];

        foreach ($plugins as $plugin) {
            $updateModel->plugins[$plugin->packageName] = new PluginUpdate([
                'packageName' => $plugin->packageName,
                'localVersion' => $plugin->version
            ]);
        }

        return Craft::$app->getEt()->checkForUpdates($updateModel);
    }

    /**
     * Check plugins’ release feeds and include any pending updates in the given Update
     *
     * @param Update $update
     *
     * @return void
     */
    public function checkPluginReleaseFeeds(Update $update)
    {
        $userAgent = 'Craft/'.Craft::$app->version;

        foreach ($update->plugins as $pluginUpdate) {
            // Only check plugins where the update status isn't already known from the ET response
            if ($pluginUpdate->status !== PluginUpdateStatus::Unknown) {
                continue;
            }

            // Get the plugin and its feed URL
            /** @var Plugin $plugin */
            $plugin = Craft::$app->getPlugins()->getPluginByPackageName($pluginUpdate->packageName);

            // Skip if the plugin isn't enabled, or doesn't have a feed URL
            if ($plugin === null || $plugin->releaseFeedUrl === null) {
                continue;
            }

            // Make sure it's HTTPS
            if (strpos($plugin->releaseFeedUrl, 'https://') !== 0) {
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

                $response = $client->get($plugin->releaseFeedUrl, []);

                if ($response->getStatusCode() !== 200) {
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

                    foreach (['version', 'downloadUrl', 'date', 'notes'] as $attribute) {
                        if (empty($release[$attribute])) {
                            $missingAttributes[] = $attribute;
                        }
                    }

                    if (!empty($missingAttributes)) {
                        $errors[] = 'Missing required attributes ('.implode(', ', $missingAttributes).')';
                    }

                    // downloadUrl could be missing.
                    if (!empty($release['downloadUrl'])) {
                        // Invalid URL?
                        if (strpos($release['downloadUrl'], 'https://') !== 0) {
                            $errors[] = 'Download URL doesn’t begin with https:// ('.$release['downloadUrl'].')';
                        }
                    }

                    // release date could be missing.
                    if (!empty($release['date'])) {
                        // Invalid date?
                        $date = DateTimeHelper::toDateTime($release['date']);

                        if ($date === false) {
                            $errors[] = 'Invalid date ('.$release['date'].')';
                        }
                    }

                    // Validation complete. Were there any errors?
                    if (!empty($errors)) {
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

                    /** @noinspection UnSafeIsSetOverArrayInspection - FP */
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
                        $pluginUpdate->criticalUpdateAvailable = true;
                    }
                }

                if (!empty($releaseModels)) {
                    // Sort release models by timestamp
                    array_multisort($releaseTimestamps, SORT_DESC, $releaseModels);
                    $latestRelease = $releaseModels[0];

                    $pluginUpdate->displayName = $plugin->name;
                    $pluginUpdate->localVersion = $plugin->version;
                    $pluginUpdate->latestDate = $latestRelease->date;
                    $pluginUpdate->latestVersion = $latestRelease->version;
                    $pluginUpdate->manualDownloadEndpoint = $latestRelease->manualDownloadEndpoint;
                    $pluginUpdate->manualUpdateRequired = true;
                    $pluginUpdate->releases = $releaseModels;
                    $pluginUpdate->status = PluginUpdateStatus::UpdateAvailable;
                } else {
                    $pluginUpdate->status = PluginUpdateStatus::UpToDate;
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
            if (!FileHelper::isWritable($writablePath)) {
                $errorPath[] = $writablePath;
            }
        }

        return $errorPath;
    }

    /**
     * @param bool   $manual
     * @param string $handle
     *
     * @return array
     */
    public function prepareUpdate(bool $manual, string $handle): array
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
                $update = $this->getUpdates();

                if ($handle === 'craft') {
                    Craft::info('Updating from '.$update->app->localVersion.' to '.$update->app->latestVersion.'.');
                } else {
                    if (($plugin = Craft::$app->getPlugins()->getPlugin($handle)) === null) {
                        throw new InvalidPluginException($handle);
                    }
                    /** @var Plugin $plugin */
                    if (!isset($update->plugins[$plugin->packageName])) {
                        throw new Exception("No update info is known for the plugin \"{$handle}\".");
                    }
                    $pluginUpdate = $update->plugins[$plugin->packageName];
                    Craft::info("Updating plugin \"{$handle}\" from {$pluginUpdate->localVersion} to {$pluginUpdate->latestVersion}.");
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
    public function processUpdateDownload(string $md5, string $handle): array
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
    public function backupFiles(string $uid, string $handle): array
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
    public function updateFiles(string $uid, string $handle): array
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
    public function backupDatabase(): array
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
    public function updateDatabase(string $handle): array
    {
        Craft::info('Starting to update the database.', __METHOD__);

        try {
            $updater = new Updater();

            if ($handle === 'craft') {
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
     * @param string|false $uid
     * @param string       $handle
     *
     * @return void
     */
    public function updateCleanUp($uid, string $handle)
    {
        Craft::info('Starting to clean up after the update.', __METHOD__);

        try {
            $updater = new Updater();
            $updater->cleanUp($uid, $handle);

            // Take the site out of maintenance mode.
            Craft::info('Taking the site out of maintenance mode.', __METHOD__);
            Craft::$app->disableMaintenanceMode();

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
     * @param string|false $uid
     * @param string       $handle
     * @param string|bool  $dbBackupPath
     *
     * @return array
     */
    public function rollbackUpdate($uid, string $handle, $dbBackupPath = false): array
    {
        try {
            // Fire an 'updateFailure' event
            $this->trigger(self::EVENT_UPDATE_FAILURE, new UpdateEvent([
                'handle' => $handle,
            ]));

            $config = Craft::$app->getConfig();
            $config->maxPowerCaptain();

            if ($dbBackupPath !== false && $config->get('backupOnUpdate') && $config->get('restoreOnUpdateFailure') && $config->get('restoreCommand') !== false) {
                Craft::info('Rolling back any database changes.', __METHOD__);
                UpdateHelper::rollBackDatabaseChanges($dbBackupPath);
                Craft::info('Done rolling back any database changes.', __METHOD__);
            }

            // If uid !== false, it's an auto-update.
            if ($uid !== false) {
                Craft::info('Rolling back any file changes.', __METHOD__);
                $manifestData = UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid), $handle);

                if (!empty($manifestData)) {
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
     * @return bool
     */
    public function getIsPluginDbUpdateNeeded(): bool
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
     * Returns whether a different Craft version has been uploaded.
     *
     * @return bool
     */
    public function getHasCraftVersionChanged(): bool
    {
        return (Craft::$app->version != Craft::$app->getInfo()->version);
    }

    /**
     * Returns true if the version stored in craft_info is less than the minimum required version on the file system. This
     *
     * This effectively makes sure that a user cannot manually update past a manual breakpoint.
     *
     * @return bool
     */
    public function getIsBreakpointUpdateNeeded(): bool
    {
        return version_compare(Craft::$app->minVersionRequired, Craft::$app->getInfo()->version, '>');
    }

    /**
     * Returns whether the uploaded DB schema is equal to or greater than the installed schema.
     *
     * @return bool
     */
    public function getIsSchemaVersionCompatible(): bool
    {
        $storedSchemaVersion = Craft::$app->getInfo()->schemaVersion;

        return version_compare(Craft::$app->schemaVersion, $storedSchemaVersion, '>=');
    }

    /**
     * Returns whether Craft needs to run any database migrations.
     *
     * @return bool
     */
    public function getIsCraftDbMigrationNeeded(): bool
    {
        if ($this->_isCraftDbMigrationNeeded === null) {
            $storedSchemaVersion = Craft::$app->getInfo()->schemaVersion;
            $this->_isCraftDbMigrationNeeded = version_compare(Craft::$app->schemaVersion, $storedSchemaVersion, '>');
        }

        return $this->_isCraftDbMigrationNeeded;
    }

    /**
     * Updates the Craft version info in the craft_info table.
     *
     * @return bool
     */
    public function updateCraftVersionInfo(): bool
    {
        $info = Craft::$app->getInfo();
        $info->version = Craft::$app->version;
        $info->schemaVersion = Craft::$app->schemaVersion;

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
