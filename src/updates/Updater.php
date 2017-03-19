<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\updates;

use Craft;
use craft\base\Plugin;
use craft\base\PluginInterface;
use craft\enums\PatchManifestFileAction;
use craft\errors\DbUpdateException;
use craft\errors\DownloadPackageException;
use craft\errors\FileException;
use craft\errors\FilePermissionsException;
use craft\errors\InvalidPluginException;
use craft\errors\MinimumRequirementException;
use craft\errors\MissingFileException;
use craft\errors\UnpackPackageException;
use craft\errors\ValidatePackageException;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\Update;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\UserException;
use yii\helpers\Markdown;
use ZipArchive;

/**
 * Class Updater
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Updater
{
    // Public Methods
    // =========================================================================

    /**
     * Constructor
     */
    public function __construct()
    {
        App::maxPowerCaptain();
    }

    /**
     * @param string $handle
     *
     * @return array
     * @throws InvalidPluginException if $handle is not "craft" and not a valid plugin handle
     */
    public function getUpdateFileInfo(string $handle): array
    {
        if ($handle !== 'craft') {
            // Get the plugin's package name for ET
            if (($plugin = Craft::$app->getPlugins()->getPlugin($handle)) === null) {
                throw new InvalidPluginException($handle);
            }
            /** @var Plugin $plugin */
            $handle = $plugin->packageName;
        }

        $md5 = Craft::$app->getEt()->getUpdateFileInfo($handle);

        return ['md5' => $md5];
    }

    /**
     * @param string $md5
     * @param string $handle
     *
     * @return array
     * @throws DownloadPackageException
     * @throws Exception
     * @throws MinimumRequirementException
     * @throws UnpackPackageException
     * @throws ValidatePackageException
     */
    public function processDownload(string $md5, string $handle): array
    {
        Craft::info('Starting to process the update download.', __METHOD__);
        $tempPath = Craft::$app->getPath()->getTempPath();

        // Download the package from ET.
        Craft::info('Downloading patch file to '.$tempPath, __METHOD__);
        if (($filename = Craft::$app->getEt()->downloadUpdate($tempPath, $md5, $handle)) !== false) {
            $downloadFilePath = $tempPath.DIRECTORY_SEPARATOR.$filename;
        } else {
            throw new DownloadPackageException(Craft::t('app', 'There was a problem downloading the package.'));
        }

        $uid = StringHelper::UUID();

        // Validate the downloaded update against ET.
        Craft::info('Validating downloaded update.', __METHOD__);

        if (!$this->_validateUpdate($downloadFilePath, $md5)) {
            throw new ValidatePackageException(Craft::t('app', 'There was a problem validating the downloaded package.'));
        }

        // Unpack the downloaded package.
        Craft::info('Unpacking the downloaded package.', __METHOD__);
        $unzipFolder = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.$uid;

        if (!$this->_unpackPackage($downloadFilePath, $unzipFolder)) {
            throw new UnpackPackageException(Craft::t('app', 'There was a problem unpacking the downloaded package.'));
        }

        if ($handle === 'craft') {
            Craft::info('Validating any new requirements from the patch file.');
            $errors = $this->_validateNewRequirements($unzipFolder);

            if (!empty($errors)) {
                throw new MinimumRequirementException(Markdown::process(Craft::t('app', 'Your server does not meet the following minimum requirements for Craft CMS to run:')."\n\n".$this->_markdownList($errors)));
            }
        }

        // Validate that the paths in the update manifest file are all writable by Craft
        Craft::info('Validating update manifest file paths are writable.', __METHOD__);
        $writableErrors = $this->_validateManifestPathsWritable($unzipFolder, $handle);

        if (count($writableErrors) > 0) {
            throw new FilePermissionsException(Markdown::process(Craft::t('app',
                    'Craft CMS needs to be able to write to the following paths, but can’t:')."\n\n".$this->_markdownList($writableErrors)));
        }

        return ['uid' => $uid];
    }

    /**
     * @param string $uid
     * @param string $handle
     *
     * @throws FileException
     */
    public function backupFiles(string $uid, string $handle)
    {
        $unzipFolder = Update::getUnzipFolderFromUID($uid);

        // Backup any files about to be updated.
        Craft::info('Backing up files that are about to be updated.', __METHOD__);
        if (!$this->_backupFiles($unzipFolder, $handle)) {
            throw new FileException(Craft::t('app', 'There was a problem backing up your files for the update.'));
        }
    }

    /**
     * @param string $uid
     * @param string $handle
     *
     * @throws Exception
     * @return void
     */
    public function updateFiles(string $uid, string $handle)
    {
        $unzipFolder = Update::getUnzipFolderFromUID($uid);

        // Put the site into maintenance mode.
        Craft::info('Putting the site into maintenance mode.', __METHOD__);
        Craft::$app->enableMaintenanceMode();

        // Update the files.
        Craft::info('Performing file update.', __METHOD__);
        $manifestData = Update::getManifestData($unzipFolder, $handle);
        if ($manifestData === null || Update::doFileUpdate($manifestData, $unzipFolder, $handle) === false) {
            throw new FileException(Craft::t('app', 'There was a problem updating your files.'));
        }
    }

    /**
     * @throws Exception
     * @return string
     * @thorws \Exception in case of failure
     */
    public function backupDatabase(): string
    {
        Craft::info('Starting to backup database.', __METHOD__);
        $path = Craft::$app->getDb()->backup();

        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * @param PluginInterface|null $plugin
     *
     * @throws DbUpdateException
     * @throws Exception
     */
    public function updateDatabase(PluginInterface $plugin = null)
    {
        Craft::info('Running migrations...', __METHOD__);

        if ($plugin === null) {
            $result = Craft::$app->getMigrator()->up();
        } else {
            /** @var Plugin $plugin */
            $pluginInfo = Craft::$app->getPlugins()->getStoredPluginInfo($plugin->handle);
            $result = $plugin->update($pluginInfo['version']);
        }

        if ($result === false) {
            throw new DbUpdateException(Craft::t('app', 'There was a problem updating your database.'));
        }

        // If plugin is null we're looking at Craft.
        if ($plugin === null) {
            // Setting new Craft info.
            Craft::info('Setting new Craft CMS release info in craft_info table.', __METHOD__);

            if (!Craft::$app->getUpdates()->updateCraftVersionInfo()) {
                throw new DbUpdateException(Craft::t('app', 'The update was performed successfully, but there was a problem setting the new info in the database info table.'));
            }
        } else {
            if (!Craft::$app->getUpdates()->setNewPluginInfo($plugin)) {
                throw new DbUpdateException(Craft::t('app', 'The update was performed successfully, but there was a problem setting the new info in the plugins table.'));
            }
        }
    }

    /**
     * @param string|false $uid
     * @param string       $handle
     *
     * @return bool
     * @throws UserException
     */
    public function cleanUp($uid, string $handle): bool
    {
        // Clear the update info cache
        Craft::info('Flushing update info from cache.', __METHOD__);
        if (!Craft::$app->getCache()->flush()) {
            Craft::error('Could not flush the update info from cache.', __METHOD__);
        }

        // Clear the compiled templates
        Craft::info('Deleting compiled templates.', __METHOD__);
        $compiledTemplatesPath = Craft::$app->getPath()->getCompiledTemplatesPath();
        if (is_dir($compiledTemplatesPath)) {
            try {
                FileHelper::clearDirectory($compiledTemplatesPath);
            } catch (\Exception $e) {
                Craft::error('Could not delete compiled templates: '.$e->getMessage(), __METHOD__);
            }
        }

        // If uid !== false, then it's an auto-update.
        if ($uid !== false) {
            $unzipFolder = Update::getUnzipFolderFromUID($uid);

            // Clean-up any leftover files.
            Craft::info('Cleaning up temp files after update.', __METHOD__);
            $this->_cleanTempFiles($unzipFolder, $handle);
        }

        Craft::info('Finished Updater.', __METHOD__);

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Remove any temp files that might have been created.
     *
     * @param string $unzipFolder
     * @param string $handle
     *
     * @return void
     */
    private function _cleanTempFiles(string $unzipFolder, string $handle)
    {
        $basePath = Update::getBasePath($handle);
        $manifestData = Update::getManifestData($unzipFolder, $handle);

        if (!empty($manifestData)) {
            // Find all the .bak files
            $filesToDelete = FileHelper::findFiles($basePath, ['only' => ['*.bak']]);

            // Add all the files that were marked for deletion in the manifest file
            foreach ($manifestData as $line) {
                if (Update::isManifestVersionInfoLine($line)) {
                    continue;
                }
                list($relPath, $action) = Update::parseManifestLine($line);
                if ($action != PatchManifestFileAction::Remove) {
                    continue;
                }
                $filesToDelete[] = $basePath.DIRECTORY_SEPARATOR.FileHelper::normalizePath($relPath);
            }

            foreach ($filesToDelete as $fileToDelete) {
                if (!is_file($fileToDelete)) {
                    continue;
                }

                Craft::info('Deleting file: '.$fileToDelete, __METHOD__);
                try {
                    FileHelper::removeFile($fileToDelete);
                } catch (ErrorException $e) {
                    Craft::warning("Unable to delete the file \"{$fileToDelete}\": ".$e->getMessage(), __METHOD__);
                }

                // Delete empty directories
                $dir = dirname($filesToDelete);
                while ($dir != $basePath && FileHelper::isDirectoryEmpty($dir)) {
                    FileHelper::removeDirectory($dir);
                    $dir = dirname($dir);
                }
            }
        }

        // Clear the temp directory
        $tempDir = Craft::$app->getPath()->getTempPath();
        try {
            FileHelper::clearDirectory($tempDir);
        } catch (\Exception $e) {
            Craft::warning("Could not clear the directory {$tempDir}: ".$e->getMessage(), __METHOD__);
        }
    }

    /**
     * Validates that the downloaded file MD5 the MD5 of the file from Elliott
     *
     * @param string $downloadFilePath
     * @param string $sourceMD5
     *
     * @return bool
     */
    private function _validateUpdate(string $downloadFilePath, string $sourceMD5): bool
    {
        Craft::info('Validating MD5 for '.$downloadFilePath, __METHOD__);
        $localMD5 = md5_file($downloadFilePath);

        return $localMD5 === $sourceMD5;
    }

    /**
     * Unzip the downloaded update file into the temp package folder.
     *
     * @param string $downloadFilePath
     * @param string $unzipFolder
     *
     * @return bool
     */
    private function _unpackPackage(string $downloadFilePath, string $unzipFolder): bool
    {
        Craft::info('Unzipping package to '.$unzipFolder, __METHOD__);

        // Create the source folder if it doesn't exist yet
        FileHelper::createDirectory($unzipFolder);

        try {
            // Clear out any existing files in the source directory
            FileHelper::clearDirectory($unzipFolder);
        } catch (\Exception $e) {
            Craft::error("Could not clear the directory {$unzipFolder}: ".$e->getMessage(), __METHOD__);

            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($downloadFilePath, \ZipArchive::CHECKCONS) !== true) {
            Craft::error('Could not open the zip file: '.$downloadFilePath, __METHOD__);

            return false;
        }

        $success = $zip->extractTo($unzipFolder);
        $zip->close();

        if (!$success) {
            Craft::error('There was an error unzipping the file: '.$downloadFilePath, __METHOD__);

            return false;
        }

        return true;
    }

    /**
     * Checks to see if the files that we are about to update are writable by Craft.
     *
     * @param string $unzipFolder
     * @param string $handle
     *
     * @return array
     * @throws Exception if something is wrong with the update manifest data
     */
    private function _validateManifestPathsWritable(string $unzipFolder, string $handle): array
    {
        $manifestData = Update::getManifestData($unzipFolder, $handle);

        if ($manifestData === null) {
            throw new Exception('Invalid update manifest data');
        }

        $basePath = Update::getBasePath($handle);
        $writableErrors = [];

        foreach ($manifestData as $line) {
            if (Update::isManifestVersionInfoLine($line)) {
                continue;
            }

            list($relPath) = Update::parseManifestLine($line);
            $file = $basePath.DIRECTORY_SEPARATOR.FileHelper::normalizePath($relPath);

            // If the file already exists, make sure it's writable
            if (is_file($file)) {
                if (!FileHelper::isWritable($file)) {
                    $writableErrors[] = $file;
                }
            } else {
                // Find the closest parent folder that exists and see if it's writable
                $dir = dirname($file);
                $basePathDir = dirname($basePath);
                while ($dir != $basePathDir && !empty($dir) && $dir !== '.') {
                    if (is_dir($dir)) {
                        if (!FileHelper::isWritable($dir)) {
                            $writableErrors[] = $file;
                        }
                        break;
                    }
                    $dir = dirname($dir);
                }
            }
        }

        return $writableErrors;
    }

    /**
     * Attempt to backup each of the update manifest files by copying them to a file with the same name with a .bak
     * extension. If there is an exception thrown, we attempt to roll back all of the changes.
     *
     * @param string $unzipFolder
     * @param string $handle
     *
     * @return bool
     * @throws Exception if something is wrong with the update manifest data
     */
    private function _backupFiles(string $unzipFolder, string $handle): bool
    {
        $manifestData = Update::getManifestData($unzipFolder, $handle);

        if ($manifestData === null) {
            throw new Exception('Invalid update manifest data');
        }

        try {
            foreach ($manifestData as $line) {
                if (Update::isManifestVersionInfoLine($line)) {
                    continue;
                }

                // No need to back up migration files.
                if (Update::isManifestMigrationLine($line)) {
                    continue;
                }

                list($relPath) = Update::parseManifestLine($line);
                $path = Update::getBasePath($handle).DIRECTORY_SEPARATOR.FileHelper::normalizePath($relPath);

                if (is_file($path)) {
                    Craft::info('Backing up file '.$path, __METHOD__);
                    copy($path, $path.'.bak');
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
     * Note this method will only ever run in the context of an auto-update and
     * won't run on a Composer install.
     *
     * @param string $unzipFolder
     *
     * @throws Exception
     * @return array
     */
    private function _validateNewRequirements(string $unzipFolder): array
    {
        $requirementsFolderPath = FileHelper::normalizePath($unzipFolder.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'requirements');
        $requirementsFile = FileHelper::normalizePath($requirementsFolderPath.DIRECTORY_SEPARATOR.'requirements.php');
        $errors = [];

        if (!is_file($requirementsFile)) {
            throw new MissingFileException(Craft::t('app', 'The requirements file is required and it does not exist at {path}.', ['path' => $requirementsFile]));
        }

        // Make sure we can write to craft/app/requirements
        if (!FileHelper::isWritable(Craft::$app->getPath()->getAppPath().DIRECTORY_SEPARATOR.'requirements')) {
            throw new FilePermissionsException(Markdown::process(Craft::t('app', 'Craft CMS needs to be able to write to your craft/app/requirements folder and cannot. Please check your [permissions]({url}).', ['url' => 'http://craftcms.com/docs/updating#one-click-updating'])));
        }

        // Make a dupe of the requirements file and give it a random file name.
        $tempFilename = StringHelper::UUID().'.php';
        copy($requirementsFile, $requirementsFolderPath.DIRECTORY_SEPARATOR.$tempFilename);

        // Copy the random file name requirements to the requirements folder.
        // We don't want to execute any PHP from the storage folder.
        $newTempFilePath = Craft::$app->getBasePath().DIRECTORY_SEPARATOR.'requirements'.DIRECTORY_SEPARATOR.$tempFilename;
        copy($requirementsFolderPath.DIRECTORY_SEPARATOR.$tempFilename, $newTempFilePath);

        require_once Craft::$app->getBasePath().DIRECTORY_SEPARATOR.'requirements'.DIRECTORY_SEPARATOR.'RequirementsChecker.php';

        // Run the requirements checker
        $reqCheck = new \RequirementsChecker();
        $reqCheck->check($newTempFilePath);

        if ($reqCheck->result['summary']['errors'] > 0) {
            foreach ($reqCheck->getResult()['requirements'] as $req) {
                if ($req['failed'] === true) {
                    Craft::error('Requirement "'.$req['name'].'" failed with the message: '.$req['memo'], __METHOD__);
                    $errors[] = $req['memo'];
                }
            }
        }

        // Cleanup
        FileHelper::removeFile($newTempFilePath);

        return $errors;
    }

    /**
     * Turns an array of messages into a Markdown-formatted bulleted list.
     *
     * @param array $messages
     *
     * @return string
     */
    private function _markdownList(array $messages): string
    {
        $list = '';

        foreach ($messages as $message) {
            $list .= '- '.$message."\n";
        }

        return $list;
    }
}
