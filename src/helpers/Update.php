<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use craft\base\Plugin;
use craft\enums\PatchManifestFileAction;
use craft\errors\InvalidPluginException;
use yii\base\Exception;

/**
 * Update helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Update
{
    // Properties
    // =========================================================================

    /**
     * @var array|false
     */
    private static $_manifestData;

    // Public Methods
    // =========================================================================

    /**
     * Returns the base path for a given update handle.
     *
     * @param string $handle
     *
     * @return string
     */
    public static function getBasePath(string $handle): string
    {
        if ($handle === 'craft') {
            return Craft::$app->getPath()->getAppPath();
        }

        return Craft::$app->getPath()->getPluginsPath().DIRECTORY_SEPARATOR.$handle;
    }

    /**
     * Returns an array containing the relative path and the update action
     * from a given line in the manifest.
     *
     * @param string $line
     *
     * @return array
     */
    public static function parseManifestLine(string $line): array
    {
        return array_map('trim', explode(';', $line, 2));
    }

    /**
     * @param array  $manifestData
     * @param string $handle
     *
     * @return void
     */
    public static function rollBackFileChanges(array $manifestData, string $handle)
    {
        foreach ($manifestData as $line) {
            if (static::isManifestVersionInfoLine($line)) {
                continue;
            }

            if (static::isManifestMigrationLine($line)) {
                continue;
            }

            list($relPath) = static::parseManifestLine($line);
            $path = static::getBasePath($handle).DIRECTORY_SEPARATOR.FileHelper::normalizePath($relPath);
            $backupPath = $path.'.bak';

            if (!is_file($backupPath)) {
                continue;
            }

            rename($backupPath, $path);
        }
    }

    /**
     * Rolls back any changes made to the DB during the update process.
     *
     * @param string $backupPath
     *
     * @return bool
     */
    public static function rollBackDatabaseChanges(string $backupPath): bool
    {
        $fileName = $backupPath.'.sql';
        $fullBackupPath = Craft::$app->getPath()->getDbBackupPath().DIRECTORY_SEPARATOR.$fileName;

        // Make sure we're constrained to the backups folder.
        if (!Path::ensurePathIsContained($fileName)) {
            Craft::warning('Someone tried to restore a database from outside of the Craft backups folder: '.$fullBackupPath, __METHOD__);

            return false;
        }

        Craft::$app->getDb()->restore($fullBackupPath);
        return true;
    }

    /**
     * @param array  $manifestData
     * @param string $sourceTempFolder
     * @param string $handle
     *
     * @return bool
     */
    public static function doFileUpdate(array $manifestData, string $sourceTempFolder, string $handle): bool
    {
        $destDirectory = static::getBasePath($handle);

        if ($handle === 'craft') {
            // Pull files from the app/ subdirectory in the temp folder
            $sourceTempFolder .= DIRECTORY_SEPARATOR.'app';
        }

        try {
            foreach ($manifestData as $line) {
                if (static::isManifestVersionInfoLine($line)) {
                    continue;
                }

                list($relPath, $action) = static::parseManifestLine($line);

                // We'll deal with removed files later
                if ($action != PatchManifestFileAction::Add) {
                    continue;
                }

                $normalizedRelPath = FileHelper::normalizePath($relPath);
                $destPath = $destDirectory.DIRECTORY_SEPARATOR.$normalizedRelPath;
                $sourcePath = $sourceTempFolder.DIRECTORY_SEPARATOR.$normalizedRelPath;

                Craft::info('Updating file: '.$destPath, __METHOD__);
                copy($sourcePath, $destPath);

                // Invalidate opcache
                if (function_exists('opcache_invalidate')) {
                   @opcache_invalidate($destPath, true);
                }
            }
        } catch (\Exception $e) {
            Craft::error('Error updating files: '.$e->getMessage(), __METHOD__);
            Update::rollBackFileChanges($manifestData, $handle);

            return false;
        }

        return true;
    }

    /**
     * @param string $line
     *
     * @return bool
     */
    public static function isManifestVersionInfoLine(string $line): bool
    {
        return strpos($line, '##') === 0;
    }

    /**
     * Returns the local version number from the given manifest file.
     *
     * @param array $manifestData
     *
     * @return false|string
     */
    public static function getLocalVersionFromManifest(array $manifestData)
    {
        if (!static::isManifestVersionInfoLine($manifestData[0])) {
            return false;
        }

        preg_match('/^##(.*);/', $manifestData[0], $matches);

        return $matches[1];
    }

    /**
     * Return true if line is a manifest migration line.
     *
     * @param string $line
     *
     * @return bool
     */
    public static function isManifestMigrationLine(string $line): bool
    {
        if (StringHelper::contains($line, 'migrations/')) {
            return true;
        }

        return false;
    }

    /**
     * Returns the relevant lines from the update manifest file starting with the current local version.
     *
     * @param string $manifestDataPath
     * @param string $handle
     *
     * @return array|null
     * @throws Exception if there was a problem reading the update manifest data
     * @throws InvalidPluginException if $handle is not "craft" and not a valid plugin handle
     */
    public static function getManifestData(string $manifestDataPath, string $handle)
    {
        if (self::$_manifestData !== null) {
            return self::$_manifestData ?: null;
        }

        $fullPath = FileHelper::normalizePath(rtrim($manifestDataPath, '/\\').DIRECTORY_SEPARATOR.$handle.'_manifest');

        if (!is_file($fullPath)) {
            self::$_manifestData = false;

            return null;
        }

        // Get an array of the lines in the manifest file
        if (($manifestData = file($fullPath)) === false) {
            throw new Exception('There was a problem reading the update manifest data');
        }

        $manifestData = array_filter(array_map('trim', $manifestData));
        $update = Craft::$app->getUpdates()->getUpdates();

        if ($handle === 'craft') {
            $localVersion = $update->app->localVersion;
        } else {
            if (($plugin = Craft::$app->getPlugins()->getPlugin($handle)) === null) {
                throw new InvalidPluginException($handle);
            }
            /** @var Plugin $plugin */
            if (!isset($update->plugins[$plugin->packageName])) {
                throw new Exception("No update info is known for the plugin \"{$handle}\".");
            }
            $localVersion = $update->plugins[$plugin->packageName]->localVersion;
        }

        // Only use the manifest data starting from the local version
        $counter = 0;
        /** @noinspection ForeachSourceInspection - FP */
        foreach ($manifestData as $counter => &$line) {
            if (StringHelper::contains($line, '##'.$localVersion)) {
                break;
            }
        }
        unset($line);
        $manifestData = array_slice($manifestData, $counter);

        if (empty($manifestData)) {
            self::$_manifestData = false;

            return null;
        }

        self::$_manifestData = $manifestData;

        return $manifestData;
    }

    /**
     * @param string $uid
     *
     * @return string
     */
    public static function getUnzipFolderFromUID(string $uid): string
    {
        return Craft::$app->getPath()->getTempPath().'/'.$uid;
    }

    /**
     * @param string $uid
     *
     * @return string
     */
    public static function getZipFileFromUID(string $uid): string
    {
        return Craft::$app->getPath()->getTempPath().'/'.$uid.'.zip';
    }
}
