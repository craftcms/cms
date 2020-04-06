<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Plugin;
use craft\base\PluginInterface;
use craft\db\Table;
use craft\errors\MigrateException;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\models\Updates as UpdatesModel;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;

/**
 * Updates service.
 * An instance of the Updates service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getUpdates()|`Craft::$app->updates`]].
 *
 * @property bool $isCraftDbMigrationNeeded Whether Craft needs to run any database migrations
 * @property bool $isCraftSchemaVersionCompatible Whether the uploaded DB schema is equal to or greater than the installed schema
 * @property bool $isCriticalUpdateAvailable Whether a critical update is available
 * @property bool $isPluginDbUpdateNeeded Whether a plugin needs to run a database update
 * @property bool $isUpdateInfoCached Whether the update info is cached
 * @property bool $wasCraftBreakpointSkipped Whether the build stored in craft_info is less than the minimum required build on the file system
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @internal
 */
class Updates extends Component
{
    /**
     * @var string
     */
    public $cacheKey = 'updates';

    /**
     * @var UpdatesModel|null
     */
    private $_updates;

    /**
     * @var bool|null
     */
    private $_isCraftDbMigrationNeeded;

    /**
     * Returns whether the update info is cached.
     *
     * @return bool
     */
    public function getIsUpdateInfoCached(): bool
    {
        return ($this->_updates !== null || Craft::$app->getCache()->exists($this->cacheKey));
    }

    /**
     * @param bool $check Whether to check for updates if they aren't cached already
     * @return int
     */
    public function getTotalAvailableUpdates(bool $check = false): int
    {
        if (!$check && !$this->getIsUpdateInfoCached()) {
            return 0;
        }
        return $this->getUpdates()->getTotal();
    }

    /**
     * Returns whether a critical update is available.
     *
     * @param bool $check Whether to check for updates if they aren't cached already
     * @return bool
     */
    public function getIsCriticalUpdateAvailable(bool $check = false): bool
    {
        if (!$check && !$this->getIsUpdateInfoCached()) {
            return false;
        }
        return $this->getUpdates()->getHasCritical();
    }

    /**
     * @param bool $refresh
     * @return UpdatesModel
     */
    public function getUpdates(bool $refresh = false): UpdatesModel
    {
        if (!$refresh) {
            if ($this->_updates !== null) {
                return $this->_updates;
            }

            if (($cached = Craft::$app->getCache()->get($this->cacheKey)) !== false) {
                return $this->_updates = new UpdatesModel($cached);
            }
        }

        try {
            $updateData = Craft::$app->getApi()->getUpdates();
        } catch (\Throwable $e) {
            Craft::warning("Couldn't get updates: {$e->getMessage()}", __METHOD__);
            $updateData = [];
        }

        return $this->cacheUpdates($updateData);
    }

    /**
     * Caches new update info.
     *
     * @param array $updateData
     * @return UpdatesModel
     * @since 3.3.16
     */
    public function cacheUpdates(array $updateData): UpdatesModel
    {
        // Instantiate the Updates model first so we don't chance caching bad data
        $updates = new UpdatesModel($updateData);
        // Cache for 5 minutes or 24 hours depending on whether we actually have results
        $cacheDuration = empty($updateData) ? 300 : 86400;
        Craft::$app->getCache()->set($this->cacheKey, $updateData, $cacheDuration);
        return $this->_updates = $updates;
    }

    /**
     * @param PluginInterface $plugin
     * @return bool
     */
    public function setNewPluginInfo(PluginInterface $plugin): bool
    {
        /** @var Plugin $plugin */
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->update(
                Table::PLUGINS,
                [
                    'version' => $plugin->getVersion(),
                    'schemaVersion' => $plugin->schemaVersion
                ],
                ['handle' => $plugin->id])
            ->execute();

        // Only update the schema version if it's changed from what's in the file,
        // so we don't accidentally overwrite other pending changes
        $projectConfig = Craft::$app->getProjectConfig();
        $key = Plugins::CONFIG_PLUGINS_KEY . '.' . $plugin->handle . '.schemaVersion';

        if ($projectConfig->get($key, true) !== $plugin->schemaVersion) {
            Craft::$app->getProjectConfig()->set($key, $plugin->schemaVersion, "Update plugin schema version for “{$plugin->handle}”");
        }

        return (bool)$affectedRows;
    }

    /**
     * Returns a list of things with updated schema versions.
     *
     * Craft CMS will be represented as "craft", plugins will be represented by their handles, and content will be represented as "content".
     *
     * @param bool $includeContent Whether pending content migrations should be considered
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
                    Craft::$app->getUpdates()->updateCraftVersionInfo();
                } else if ($handle === 'content') {
                    Craft::$app->getContentMigrator()->up();
                } else {
                    /** @var Plugin $plugin */
                    $plugin = Craft::$app->getPlugins()->getPlugin($handle);
                    $name = $plugin->name;
                    $plugin->getMigrator()->up();
                    Craft::$app->getUpdates()->setNewPluginInfo($plugin);
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new MigrateException($name, $handle, null, 0, $e);
        }

        // Delete all compiled templates
        try {
            FileHelper::clearDirectory(Craft::$app->getPath()->getCompiledTemplatesPath(false));
        } catch (InvalidArgumentException $e) {
            // the directory doesn't exist
        } catch (ErrorException $e) {
            Craft::error('Could not delete compiled templates: ' . $e->getMessage());
            Craft::$app->getErrorHandler()->logException($e);
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

        Craft::$app->saveInfo($info);

        // Only update the schema version if it's changed from what's in the file,
        // so we don't accidentally overwrite other pending changes
        $projectConfig = Craft::$app->getProjectConfig();
        if ($projectConfig->get(ProjectConfig::CONFIG_SCHEMA_VERSION_KEY, true) !== $info->schemaVersion) {
            Craft::$app->getProjectConfig()->set(ProjectConfig::CONFIG_SCHEMA_VERSION_KEY, $info->schemaVersion, 'Update Craft schema version');
        }

        return true;
    }
}
