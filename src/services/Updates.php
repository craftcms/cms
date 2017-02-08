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
use craft\helpers\Update as UpdateHelper;
use craft\models\AppUpdate;
use craft\models\AppUpdateRelease;
use craft\models\PluginUpdate;
use craft\models\Update;
use craft\models\UpdateRelease;
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
     * @var bool Whether to require changelog URLs to begin with https://
     */
    public $requireHttpsForChangelogUrls = true;

    /**
     * @var Update|null
     */
    private $_updateModel;

    /**
     * @var bool|null
     */
    private $_isCraftDbMigrationNeeded;

    // Public Methods
    // =========================================================================

    /**
     * @param AppUpdateRelease[] $craftReleases
     *
     * @return bool
     */
    public function criticalCraftUpdateAvailable(array $craftReleases): bool
    {
        foreach ($craftReleases as $craftRelease) {
            if ($craftRelease->critical) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PluginUpdate[] $pluginUpdate
     *
     * @return bool
     */
    public function criticalPluginUpdateAvailable(array $pluginUpdate): bool
    {
        foreach ($pluginUpdate as $pluginRelease) {
            if ($pluginRelease->status === PluginUpdateStatus::UpdateAvailable && count($pluginRelease->releases) > 0) {
                foreach ($pluginRelease->releases as $release) {
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
        if ($forceRefresh === false && $this->_updateModel !== null) {
            return $this->_updateModel;
        }

        // Check cache?
        if ($forceRefresh === false && ($cachedUpdate = Craft::$app->getCache()->get('updateinfo')) !== false) {
            return $this->_updateModel = $cachedUpdate;
        }

        if (($this->_updateModel = $this->checkForUpdates()) === null) {
            return $this->_updateModel = new Update([
                'responseErrors' => [
                    Craft::t('app', 'Craft is unable to determine if any updates are available at this time.')
                ]
            ]);
        }

        // Cache it
        Craft::$app->getCache()->set('updateinfo', $this->_updateModel);

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
                ['handle' => $plugin->handle])
            ->execute();

        return (bool)$affectedRows;
    }

    /**
     * Checks for any available Craft/plugin updates.
     *
     * @return Update|null Info about the available updates, or null if it couldn't be determined
     */
    public function checkForUpdates()
    {
        Craft::$app->getConfig()->maxPowerCaptain();

        // Prep the update models
        $update = new Update();
        $update->app = new AppUpdate();
        $update->app->localVersion = Craft::$app->version;

        /** @var Plugin[] $plugins */
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($plugins as $plugin) {
            $update->plugins[$plugin->packageName] = new PluginUpdate([
                'packageName' => $plugin->packageName,
                'localVersion' => $plugin->version
            ]);
        }

        // Phone home
        if (($et = Craft::$app->getEt()->checkForUpdates($update)) === null || empty($et->data)) {
            return null;
        }

        /** @var Update $update */
        $update = $et->data;

        // Check plugin changelogs
        $this->checkPluginChangelogs($update);

        return $update;
    }

    /**
     * Check plugins’ changelogs and include any pending updates in the given Update model.
     *
     * @param Update $update
     *
     * @return void
     */
    public function checkPluginChangelogs(Update $update)
    {
        $pluginsService = Craft::$app->getPlugins();

        foreach ($update->plugins as $pluginUpdate) {
            // Only check plugins where the update status isn't already known from the ET response
            if ($pluginUpdate->status !== PluginUpdateStatus::Unknown) {
                continue;
            }

            // Get the plugin
            /** @var Plugin $plugin */
            if (($plugin = $pluginsService->getPluginByPackageName($pluginUpdate->packageName)) === null) {
                continue;
            }

            // Fetch its changelog
            if (($changelog = $this->fetchPluginChangelog($plugin)) === null) {
                continue;
            }

            // Get the new releases
            $releaseModels = $this->parsePluginChangelog($plugin, $changelog);

            if (!empty($releaseModels)) {
                $latestRelease = $releaseModels[0];
                $pluginUpdate->status = PluginUpdateStatus::UpdateAvailable;
                $pluginUpdate->displayName = $plugin->name;
                $pluginUpdate->localVersion = $plugin->version;
                $pluginUpdate->latestDate = $latestRelease->date;
                $pluginUpdate->latestVersion = $latestRelease->version;
                $pluginUpdate->manualDownloadEndpoint = $plugin->downloadUrl;
                $pluginUpdate->manualUpdateRequired = true;
                $pluginUpdate->releases = $releaseModels;
            } else {
                $pluginUpdate->status = PluginUpdateStatus::UpToDate;
            }
        }
    }

    /**
     * Fetches a plugin’s changelog
     *
     * @param PluginInterface $plugin
     *
     * @return string|null
     */
    public function fetchPluginChangelog(PluginInterface $plugin)
    {
        /** @var Plugin $plugin */

        // Skip if the plugin isn't enabled, or doesn't have a changelog URL
        if ($plugin->changelogUrl === null) {
            return null;
        }

        // Make sure it's HTTPS
        if ($this->requireHttpsForChangelogUrls && strpos($plugin->changelogUrl, 'https://') !== 0) {
            Craft::warning('The “'.$plugin->name.'” plugin has a changelog URL, but it doesn’t begin with https://, so it’s getting skipped ('.$plugin->changelogUrl.').', __METHOD__);

            return null;
        }

        try {
            // Fetch it
            $client = new Client([
                'headers' => [
                    'User-Agent' => 'Craft/'.Craft::$app->version,
                ],
                'timeout' => 5,
                'connect_timeout' => 2,
                'allow_redirects' => true,
                'verify' => false
            ]);

            // Potentially long-running request, so close session to prevent session blocking on subsequent requests.
            Craft::$app->getSession()->close();

            $response = $client->get($plugin->changelogUrl, []);

            if ($response->getStatusCode() !== 200) {
                Craft::warning('Error in calling '.$plugin->changelogUrl.'. Response: '.$response->getBody(), __METHOD__);

                return null;
            }

            return (string)$response->getBody();
        } catch (\Exception $e) {
            Craft::error('There was a problem getting the changelog for “'.$plugin->name.'”, so it was skipped: '.$e->getMessage(), __METHOD__);

            return null;
        }
    }

    /**
     * Parses a plugin’s changelog and returns an array of PluginUpdateRelease models.
     *
     * @param PluginInterface $plugin
     * @param string          $changelog
     *
     * @return UpdateRelease[]
     */
    public function parsePluginChangelog(PluginInterface $plugin, string $changelog): array
    {
        /** @var Plugin $plugin */
        $releases = [];

        $currentRelease = null;
        $currentNotes = '';

        // Move the changelog to a temp file
        $file = tmpfile();
        fwrite($file, $changelog);
        fseek($file, 0);

        while (($line = fgets($file)) !== false) {
            // Is this an H1 or H2?
            /** @noinspection StrNcmpUsedAsStrPosInspection */
            if (strncmp($line, '# ', 2) === 0 || strncmp($line, '## ', 3) === 0) {
                // If we're in the middle of getting a release's notes, finish it off
                if ($currentRelease !== null) {
                    $this->addNotesToPluginRelease($currentRelease, $currentNotes);
                    $currentRelease = null;
                }

                // Is it an H2 version heading?
                if (preg_match('/^## \[?v?(\d+\.\d+\.\d+(?:\.\d+)?(?:-[0-9A-Za-z-\.]+)?)\]?(?:\(.*?\)|\[.*?\])? - (\d{4}-\d\d?-\d\d?)( \[critical\])?/i', $line, $match)) {
                    // Is it <= the current plugin version?
                    if (version_compare($match[1], $plugin->version, '<=')) {
                        break;
                    }

                    // Prep the new release
                    $currentRelease = $releases[] = new UpdateRelease();
                    $currentRelease->version = $match[1];
                    $releaseDate = DateTimeHelper::toDateTime($match[2], true);

                    if ($releaseDate === false) {
                        $releaseDate = null;
                    }

                    $currentRelease->date = $releaseDate;
                    $currentRelease->critical = !empty($match[3]);

                    $currentNotes = '';
                }
            } else if ($currentRelease !== null) {
                // Append the line to the current release notes
                $currentNotes .= $line;
            }
        }

        // Close the temp file
        fclose($file);

        // If we're in the middle of getting a release's notes, finish it off
        if ($currentRelease !== null) {
            $this->addNotesToPluginRelease($currentRelease, $currentNotes);
        }

        return $releases;
    }

    /**
     * Adds release notes to a plugin release.
     *
     * @param UpdateRelease $release
     * @param string        $notes
     *
     * @return void
     */
    public function addNotesToPluginRelease(UpdateRelease $release, string $notes)
    {
        // Encode any HTML within the notes
        $notes = htmlentities($notes, null, 'UTF-8');

        // Except for `> blockquotes`
        $notes = preg_replace('/^(\s*)&gt;/m', '$1>', $notes);

        // Parse as Markdown
        $notes = Markdown::process($notes, 'gfm');

        // Notes/tips
        $notes = preg_replace('/<blockquote><p>\{(note|tip)\}/', '<blockquote class="note $1"><p>', $notes);

        // Set them on the release model
        $release->notes = $notes;
    }

    /**
     * Checks to see if Craft can write to a defined set of folders/files that are
     * needed for auto-update to work.
     *
     * @return array
     */
    public function getUnwritableFolders(): array
    {
        $checkPaths = [
            Craft::$app->getPath()->getAppPath(),
            Craft::$app->getPath()->getPluginsPath(),
        ];

        $errorPaths = [];

        foreach ($checkPaths as $writablePath) {
            if (!FileHelper::isWritable($writablePath)) {
                $errorPaths[] = $writablePath;
            }
        }

        return $errorPaths;
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
                    Craft::info('Updating from '.$update->app->localVersion.' to '.$update->app->latestVersion.'.', __METHOD__);
                } else {
                    if (($plugin = Craft::$app->getPlugins()->getPlugin($handle)) === null) {
                        throw new InvalidPluginException($handle);
                    }

                    /** @var Plugin $plugin */
                    if (!isset($update->plugins[$plugin->packageName])) {
                        throw new Exception("No update info is known for the plugin \"{$handle}\".");
                    }

                    $pluginUpdate = $update->plugins[$plugin->packageName];
                    Craft::info("Updating plugin \"{$handle}\" from {$pluginUpdate->localVersion} to {$pluginUpdate->latestVersion}.", __METHOD__);
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
            Craft::error('Error processing the update download: '.$e->getMessage(), __METHOD__);

            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            Craft::error('Error processing the update download: '.$e->getMessage(), __METHOD__);

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

            Craft::info('Finished backing up files.', __METHOD__);

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
