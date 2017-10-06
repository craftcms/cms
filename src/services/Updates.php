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
use craft\errors\MigrateException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\models\AppUpdate;
use craft\models\PluginUpdate;
use craft\models\Update;
use craft\models\UpdateRelease;
use GuzzleHttp\Client;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\Markdown;

/**
 * Class Updates service.
 *
 * An instance of the Updates service is globally accessible in Craft via [[Application::updates `Craft::$app->getUpdates()`]].
 *
 * @property bool $isCraftDbMigrationNeeded       Whether Craft needs to run any database migrations
 * @property bool $isCraftSchemaVersionCompatible Whether the uploaded DB schema is equal to or greater than the installed schema
 * @property bool $isCriticalUpdateAvailable      Whether a critical update is available
 * @property bool $isPluginDbUpdateNeeded         Whether a plugin needs to run a database update
 * @property bool $isUpdateInfoCached             Whether the update info is cached
 * @property bool $wasCraftBreakpointSkipped      Whether the build stored in craft_info is less than the minimum required build on the file system
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Updates extends Component
{
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

        if (!empty($this->_updateModel->plugins)) {
            foreach ($this->_updateModel->plugins as $pluginUpdate) {
                if ($pluginUpdate->criticalUpdateAvailable) {
                    return true;
                }
            }
        }

        return false;
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
                    'version' => $plugin->getVersion(),
                    'schemaVersion' => $plugin->schemaVersion
                ],
                ['handle' => $plugin->id])
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
        App::maxPowerCaptain();

        // Prep the update models
        $update = new Update();
        $update->app = new AppUpdate();
        $update->app->localVersion = Craft::$app->getVersion();

        /** @var Plugin[] $plugins */
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($plugins as $plugin) {
            $update->plugins[$plugin->packageName] = new PluginUpdate([
                'packageName' => $plugin->packageName,
                'localVersion' => $plugin->getVersion()
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
                $pluginUpdate->localVersion = $plugin->getVersion();
                $pluginUpdate->latestDate = $latestRelease->date;
                $pluginUpdate->latestVersion = $latestRelease->version;
                $pluginUpdate->manualDownloadEndpoint = $plugin->downloadUrl;
                $pluginUpdate->manualUpdateRequired = true;
                $pluginUpdate->releases = $releaseModels;

                // See if there's a critical update available
                foreach ($releaseModels as $release) {
                    if ($release->critical) {
                        $pluginUpdate->criticalUpdateAvailable = true;
                        break;
                    }
                }
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
                    'User-Agent' => 'Craft/'.Craft::$app->getVersion(),
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
        } catch (\Throwable $e) {
            Craft::error('There was a problem getting the changelog for “'.$plugin->name.'”, so it was skipped: '.$e->getMessage(), __METHOD__);

            return null;
        }
    }

    /**
     * Parses a plugin’s changelog and returns an array of UpdateRelease models.
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
                if (preg_match('/^## (?:.* )?\[?v?(\d+\.\d+\.\d+(?:\.\d+)?(?:-[0-9A-Za-z-\.]+)?)\]?(?:\(.*?\)|\[.*?\])? - (\d{4}[-\.]\d\d?[-\.]\d\d?)( \[critical\])?/i', $line, $match)) {
                    // Is it <= the current plugin version?
                    if (version_compare($match[1], $plugin->getVersion(), '<=')) {
                        break;
                    }

                    // Prep the new release
                    $currentRelease = $releases[] = new UpdateRelease();
                    $currentRelease->version = $match[1];
                    $releaseDate = DateTimeHelper::toDateTime(str_replace('.', '-', $match[2]), true);

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
        $notes = preg_replace('/<blockquote><p>\{(note|tip|warning)\}/', '<blockquote class="note $1"><p>', $notes);

        // Set them on the release model
        $release->notes = $notes;
    }

    /**
     * Returns a list of things with updated schema versions.
     *
     * Craft CMS will be represented as "craft", plugins will be represented by their handles, and content will be represented as "content".
     *
     * @param bool $includeContent Whether pending content migrations should be considered
     *
     * @return string[]
     * @see runMigrations()
     */
    public function getPendingMigrationHandles($includeContent = false): array
    {
        $handles = [];

        if ($this->getIsCraftDbMigrationNeeded()) {
            $handles[] = 'craft';
        }

        $pluginsService = Craft::$app->getPlugins();
        foreach ($pluginsService->getAllPlugins() as $plugin) {
            /** @var Plugin $plugin */
            if ($pluginsService->doesPluginRequireDatabaseUpdate($plugin)) {
                $handles[] = $plugin->id;
            }
        }

        if ($includeContent) {
            $contentMigrator = Craft::$app->getContentMigrator();
            if (!empty($contentMigrator->getNewMigrations())) {
                $handles[] = 'content';
            }
        }

        return $handles;
    }

    /**
     * Runs the pending migrations for the given list of handles.
     *
     * @param string[] $handles The list of handles to run migrations for
     *
     * @return void
     * @throws MigrateException
     * @see getPendingMigrationHandles()
     */
    public function runMigrations(array $handles)
    {
        // Make sure Craft is first
        if (ArrayHelper::remove($handles, 'craft') !== null) {
            array_unshift($handles, 'craft');
        }

        // Make sure content is last
        if (ArrayHelper::remove($handles, 'content') !== null) {
            $handles[] = 'content';
        }

        // Set the name & handle early in case we need it in the catch
        $name = 'Craft CMS';
        $handle = 'craft';

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            foreach ($handles as $handle) {
                if ($handle === 'craft') {
                    Craft::$app->getMigrator()->up();
                    $versionUpdated = Craft::$app->getUpdates()->updateCraftVersionInfo();
                } else if ($handle === 'content') {
                    Craft::$app->getContentMigrator()->up();
                    $versionUpdated = true;
                } else {
                    /** @var Plugin $plugin */
                    $plugin = Craft::$app->getPlugins()->getPlugin($handle);
                    $name = $plugin->name;
                    $plugin->getMigrator()->up();
                    $versionUpdated = Craft::$app->getUpdates()->setNewPluginInfo($plugin);
                }

                if (!$versionUpdated) {
                    throw new Exception("Couldn't set new version info for $name.");
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new MigrateException($name, $handle, null, 0, $e);
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
        return (Craft::$app->getVersion() != Craft::$app->getInfo()->version);
    }

    /**
     * Returns true if the version stored in craft_info is less than the minimum required version on the file system. This
     *
     * This effectively makes sure that a user cannot manually update past a manual breakpoint.
     *
     * @return bool
     */
    public function getWasCraftBreakpointSkipped(): bool
    {
        return version_compare(Craft::$app->minVersionRequired, Craft::$app->getInfo()->version, '>');
    }

    /**
     * Returns whether the uploaded DB schema is equal to or greater than the installed schema.
     *
     * @return bool
     */
    public function getIsCraftSchemaVersionCompatible(): bool
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
        $info->version = Craft::$app->getVersion();
        $info->schemaVersion = Craft::$app->schemaVersion;

        return Craft::$app->saveInfo($info);
    }
}
