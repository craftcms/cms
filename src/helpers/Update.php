<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use craft\enums\PatchManifestFileAction;
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
    public static function getBasePath($handle)
    {
        if ($handle == 'craft') {
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
    public static function parseManifestLine($line)
    {
        return array_map('trim', explode(';', $line, 2));
    }

    /**
     * @param array  $manifestData
     * @param string $handle
     *
     * @return void
     */
    public static function rollBackFileChanges($manifestData, $handle)
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
     * @param $backupPath
     *
     * @return boolean
     */
    public static function rollBackDatabaseChanges($backupPath)
    {
        $fileName = $backupPath.'.sql';
        $fullBackupPath = Craft::$app->getPath()->getDbBackupPath().'/'.$fileName;

        // Make sure we're constrained to the backups folder.
        if (Path::ensurePathIsContained($fileName)) {
            if (Craft::$app->getDb()->restore($fullBackupPath)) {
                return true;
            } else {
                Craft::error('There was a problem restoring the database backup.', __METHOD__);
            }
        } else {
            Craft::warning('Someone tried to restore a database from outside of the Craft backups folder: '.$fullBackupPath, __METHOD__);
        }

        return false;
    }

    /**
     * @param array  $manifestData
     * @param string $sourceTempFolder
     * @param string $handle
     *
     * @return boolean
     */
    public static function doFileUpdate($manifestData, $sourceTempFolder, $handle)
    {
        $destDirectory = static::getBasePath($handle);

        if ($handle == 'craft') {
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
                    opcache_invalidate($destPath, true);
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
     * @param $line
     *
     * @return boolean
     */
    public static function isManifestVersionInfoLine($line)
    {
        return strncmp($line, '##', 2) === 0;
    }

    /**
     * Returns the local version number from the given manifest file.
     *
     * @param $manifestData
     *
     * @return boolean|string
     */
    public static function getLocalVersionFromManifest($manifestData)
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
     * @param $line
     *
     * @return boolean
     */
    public static function isManifestMigrationLine($line)
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
     * @return array
     * @throws Exception if there was a problem reading the update manifest data
     */
    public static function getManifestData($manifestDataPath, $handle)
    {
        if (static::$_manifestData !== null) {
            return static::$_manifestData ?: null;
        }

        $fullPath = FileHelper::normalizePath(rtrim($manifestDataPath, '/\\').DIRECTORY_SEPARATOR.$handle.'_manifest');

        if (!is_file($fullPath)) {
            static::$_manifestData = false;

            return null;
        }

        // Get an array of the lines in the manifest file
        if (($manifestData = file($fullPath)) === false) {
            throw new Exception('There was a problem reading the update manifest data');
        }

        $manifestData = array_filter(array_map('trim', $manifestData));
        $updateModel = Craft::$app->getUpdates()->getUpdates();
        $localVersion = null;

        if ($handle == 'craft') {
            $localVersion = $updateModel->app->localVersion;
        } else {
            foreach ($updateModel->plugins as $plugin) {
                if (strtolower($plugin->class) == $handle) {
                    $localVersion = $plugin->localVersion;
                    break;
                }
            }
        }

        // Only use the manifest data starting from the local version
        for ($counter = 0; $counter < count($manifestData); $counter++) {
            if (StringHelper::contains($manifestData[$counter], '##'.$localVersion)) {
                break;
            }
        }
        $manifestData = array_slice($manifestData, $counter);

        if (empty($manifestData)) {
            static::$_manifestData = false;

            return null;
        }

        static::$_manifestData = $manifestData;

        return $manifestData;
    }

    /**
     * @param $uid
     *
     * @return string
     */
    public static function getUnzipFolderFromUID($uid)
    {
        return Craft::$app->getPath()->getTempPath().'/'.$uid;
    }

    /**
     * @param $uid
     *
     * @return string
     */
    public static function getZipFileFromUID($uid)
    {
        return Craft::$app->getPath()->getTempPath().'/'.$uid.'.zip';
    }
}
