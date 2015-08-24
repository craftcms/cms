<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\updates;

use Craft;
use craft\app\base\BasePlugin;
use craft\app\base\Plugin;
use craft\app\base\PluginInterface;
use craft\app\enums\PatchManifestFileAction;
use craft\app\errors\Exception;
use craft\app\helpers\Io;
use craft\app\helpers\StringHelper;
use craft\app\helpers\Update;
use craft\app\io\Zip;
use yii\helpers\Markdown;

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
     * @return Updater
     */
    public function __construct()
    {
        Craft::$app->getConfig()->maxPowerCaptain();
    }

    /**
     * @throws Exception
     * @return void
     */
    public function getLatestUpdateInfo()
    {
        $updateModel = Craft::$app->getUpdates()->getUpdates(true);

        if (!empty($updateModel->errors)) {
            throw new Exception(implode(',', $updateModel->errors));
        }

        if ($updateModel->app->releases == null) {
            throw new Exception(Craft::t('app', 'Craft is already up to date.'));
        }
    }

    /**
     * @return array
     */
    public function getUpdateFileInfo()
    {
        $md5 = Craft::$app->getEt()->getUpdateFileInfo();

        return ['md5' => $md5];
    }

    /**
     * @param string $md5
     *
     * @throws Exception
     * @return array
     */
    public function processDownload($md5)
    {
        Craft::info('Starting to process the update download.', __METHOD__);
        $tempPath = Craft::$app->getPath()->getTempPath();

        // Download the package from ET.
        Craft::info('Downloading patch file to '.$tempPath, __METHOD__);
        if (($filename = Craft::$app->getEt()->downloadUpdate($tempPath,
                $md5)) !== false
        ) {
            $downloadFilePath = $tempPath.'/'.$filename;
        } else {
            throw new Exception(Craft::t('app', 'There was a problem downloading the package.'));
        }

        $uid = StringHelper::UUID();

        // Validate the downloaded update against ET.
        Craft::info('Validating downloaded update.', __METHOD__);
        if (!$this->_validateUpdate($downloadFilePath, $md5)) {
            throw new Exception(Craft::t('app', 'There was a problem validating the downloaded package.'));
        }

        // Unpack the downloaded package.
        Craft::info('Unpacking the downloaded package.', __METHOD__);
        $unzipFolder = Craft::$app->getPath()->getTempPath().'/'.$uid;

        if (!$this->_unpackPackage($downloadFilePath, $unzipFolder)) {
            throw new Exception(Craft::t('app', 'There was a problem unpacking the downloaded package.'));
        }

        Craft::info('Validating any new requirements from the patch file.');
        $errors = $this->_validateNewRequirements($unzipFolder);

        if (!empty($errors)) {
            throw new Exception(Markdown::process(Craft::t('app',
                    'Your server does not meet the following minimum requirements for Craft to run:')."\n\n".$this->_markdownList($errors)));
        }

        // Validate that the paths in the update manifest file are all writable by Craft
        Craft::info('Validating update manifest file paths are writable.', __METHOD__);
        $writableErrors = $this->_validateManifestPathsWritable($unzipFolder);

        if (count($writableErrors) > 0) {
            throw new Exception(Markdown::process(Craft::t('app',
                    'Craft needs to be able to write to the following paths, but can’t:')."\n\n".$this->_markdownList($writableErrors)));
        }

        return ['uid' => $uid];
    }

    /**
     * @param $uid
     *
     * @throws Exception
     * @return void
     */
    public function backupFiles($uid)
    {
        $unzipFolder = Update::getUnzipFolderFromUID($uid);

        // Backup any files about to be updated.
        Craft::info('Backing up files that are about to be updated.', __METHOD__);
        if (!$this->_backupFiles($unzipFolder)) {
            throw new Exception(Craft::t('app', 'There was a problem backing up your files for the update.'));
        }
    }

    /**
     * @param $uid
     *
     * @throws Exception
     * @return void
     */
    public function updateFiles($uid)
    {
        $unzipFolder = Update::getUnzipFolderFromUID($uid);

        // Put the site into maintenance mode.
        Craft::info('Putting the site into maintenance mode.', __METHOD__);
        Craft::$app->enableMaintenanceMode();

        // Update the files.
        Craft::info('Performing file update.', __METHOD__);
        if (!Update::doFileUpdate(Update::getManifestData($unzipFolder), $unzipFolder)) {
            throw new Exception(Craft::t('app', 'There was a problem updating your files.'));
        }
    }

    /**
     * @throws Exception
     * @return string
     */
    public function backupDatabase()
    {
        Craft::info('Starting to backup database.', __METHOD__);
        if (($dbBackupPath = Craft::$app->getDb()->backup()) === false) {
            throw new Exception(Craft::t('app', 'There was a problem backing up your database.'));
        } else {
            return Io::getFilename($dbBackupPath, false);
        }
    }

    /**
     * @param PluginInterface|Plugin $plugin
     *
     * @throws Exception
     * @return void
     */
    public function updateDatabase(PluginInterface $plugin = null)
    {
        Craft::info('Running migrations...', __METHOD__);

        if ($plugin === null) {
            $result = Craft::$app->getMigrator()->up();
        } else {
            $pluginInfo = Craft::$app->getPlugins()->getStoredPluginInfo($plugin->getHandle());
            $result = $plugin->update($pluginInfo['version']);
        }

        if ($result === false) {
            throw new Exception(Craft::t('app', 'There was a problem updating your database.'));
        }

        // If plugin is null we're looking at Craft.
        if ($plugin === null) {
            // Setting new Craft info.
            Craft::info('Settings new Craft release info in craft_info table.', __METHOD__);

            if (!Craft::$app->getUpdates()->updateCraftVersionInfo()) {
                throw new Exception(Craft::t('app', 'The update was performed successfully, but there was a problem setting the new info in the database info table.'));
            }
        } else {
            if (!Craft::$app->getUpdates()->setNewPluginInfo($plugin)) {
                throw new Exception(Craft::t('app', 'The update was performed successfully, but there was a problem setting the new info in the plugins table.'));
            }
        }

        // Take the site out of maintenance mode.
        Craft::info('Taking the site out of maintenance mode.', __METHOD__);
        Craft::$app->disableMaintenanceMode();
    }

    /**
     * @param $uid
     *
     * @throws Exception
     * @return boolean
     */
    public function cleanUp($uid)
    {
        // Clear the updates cache.
        Craft::info('Clearing the update cache.', __METHOD__);
        if (!Craft::$app->getUpdates()->flushUpdateInfoFromCache()) {
            throw new Exception(Craft::t('app', 'The update was performed successfully, but there was a problem invalidating the update cache.'));
        }

        // If uid !== false, then it's an auto-update.
        if ($uid !== false) {
            $unzipFolder = Update::getUnzipFolderFromUID($uid);

            // Clean-up any leftover files.
            Craft::info('Cleaning up temp files after update.', __METHOD__);
            $this->_cleanTempFiles($unzipFolder);
        }

        Craft::info('Finished Updater.', __METHOD__);

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Remove any temp files and/or folders that might have been created.
     *
     * @param string $unzipFolder
     *
     * @return void
     */
    private function _cleanTempFiles($unzipFolder)
    {
        $appPath = Craft::$app->getPath()->getAppPath();

        // Get rid of all the .bak files/folders.
        $filesToDelete = Io::getFolderContents($appPath, true, ".*\.bak$");

        // Now delete any files/folders that were marked for deletion in the manifest file.
        $manifestData = Update::getManifestData($unzipFolder);

        if ($manifestData) {
            foreach ($manifestData as $row) {
                if (Update::isManifestVersionInfoLine($row)) {
                    continue;
                }

                $rowData = explode(';', $row);

                if ($rowData[1] == PatchManifestFileAction::Remove) {
                    if (Update::isManifestLineAFolder($rowData[0])) {
                        $tempFilePath = Update::cleanManifestFolderLine($rowData[0]);
                    } else {
                        $tempFilePath = $rowData[0];
                    }

                    $filesToDelete[] = $appPath.'/'.$tempFilePath;
                }
            }

            foreach ($filesToDelete as $fileToDelete) {
                if (Io::fileExists($fileToDelete)) {
                    if (Io::isWritable($fileToDelete)) {
                        Craft::info('Deleting file: '.$fileToDelete, __METHOD__);
                        Io::deleteFile($fileToDelete, true);
                    }
                } else {
                    if (Io::folderExists($fileToDelete)) {
                        if (Io::isWritable($fileToDelete)) {
                            Craft::info('Deleting .bak folder:'.$fileToDelete, __METHOD__);
                            Io::clearFolder($fileToDelete, true);
                            Io::deleteFolder($fileToDelete, true);
                        }
                    }
                }
            }
        }

        // Clear the temp folder.
        Io::clearFolder(Craft::$app->getPath()->getTempPath(), true);
    }

    /**
     * Validates that the downloaded file MD5 the MD5 of the file from Elliott
     *
     * @param string $downloadFilePath
     * @param string $sourceMD5
     *
     * @return boolean
     */
    private function _validateUpdate($downloadFilePath, $sourceMD5)
    {
        Craft::info('Validating MD5 for '.$downloadFilePath, __METHOD__);
        $localMD5 = Io::getFileMD5($downloadFilePath);

        if ($localMD5 === $sourceMD5) {
            return true;
        }

        return false;
    }

    /**
     * Unzip the downloaded update file into the temp package folder.
     *
     * @param string $downloadFilePath
     * @param string $unzipFolder
     *
     * @return boolean
     */
    private function _unpackPackage($downloadFilePath, $unzipFolder)
    {
        Craft::info('Unzipping package to '.$unzipFolder, __METHOD__);

        if (Zip::unzip($downloadFilePath, $unzipFolder)) {
            return true;
        }

        return false;
    }

    /**
     * Checks to see if the files that we are about to update are writable by Craft.
     *
     * @param string $unzipFolder
     *
     * @return boolean
     */
    private function _validateManifestPathsWritable($unzipFolder)
    {
        $manifestData = Update::getManifestData($unzipFolder);
        $writableErrors = [];

        foreach ($manifestData as $row) {
            if (Update::isManifestVersionInfoLine($row)) {
                continue;
            }

            $rowData = explode(';', $row);
            $filePath = Io::normalizePathSeparators(Craft::$app->getPath()->getAppPath().'/'.$rowData[0]);

            if (Update::isManifestLineAFolder($filePath)) {
                $filePath = Update::cleanManifestFolderLine($filePath);
            }

            // Check to see if the file/folder we need to update is writable.
            if (Io::fileExists($filePath) || Io::folderExists($filePath)) {
                if (!Io::isWritable($filePath)) {
                    $writableErrors[] = $filePath;
                }
            } // In this case, it's an 'added' update file.
            else if (($folderPath = Io::folderExists(Io::getFolderName($filePath))) == true) {
                if (!Io::isWritable($folderPath)) {
                    $writableErrors[] = $filePath;
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
     *
     * @return boolean
     */
    private function _backupFiles($unzipFolder)
    {
        $manifestData = Update::getManifestData($unzipFolder);

        try {
            foreach ($manifestData as $row) {
                if (Update::isManifestVersionInfoLine($row)) {
                    continue;
                }

                // No need to back up migration files.
                if (Update::isManifestMigrationLine($row)) {
                    continue;
                }

                $rowData = explode(';', $row);
                $filePath = Io::normalizePathSeparators(Craft::$app->getPath()->getAppPath().'/'.$rowData[0]);

                // It's a folder
                if (Update::isManifestLineAFolder($filePath)) {
                    $folderPath = Update::cleanManifestFolderLine($filePath);
                    if (Io::folderExists($folderPath)) {
                        Craft::info('Backing up folder '.$folderPath, __METHOD__);
                        Io::createFolder($folderPath.'.bak');
                        Io::copyFolder($folderPath.'/', $folderPath.'.bak/');
                    }
                } // It's a file.
                else {
                    // If the file doesn't exist, it's probably a new file.
                    if (Io::fileExists($filePath)) {
                        Craft::info('Backing up file '.$filePath, __METHOD__);
                        Io::copyFile($filePath, $filePath.'.bak');
                    }
                }
            }
        } catch (\Exception $e) {
            Craft::error('Error updating files: '.$e->getMessage(), __METHOD__);
            Update::rollBackFileChanges($manifestData);

            return false;
        }

        return true;
    }

    /**
     * @param string $unzipFolder
     *
     * @throws Exception
     * @return array
     */
    private function _validateNewRequirements($unzipFolder)
    {
        $requirementsFolderPath = $unzipFolder.'/app/requirements';
        $requirementsFile = $requirementsFolderPath.'/requirements.php';
        $errors = [];

        if (!Io::fileExists($requirementsFile)) {
            throw new Exception(Craft::t('app', 'The requirements file is required and it does not exist at {path}.', ['path' => $requirementsFile]));
        }

        // Make sure we can write to craft/app/requirements
        if (!Io::isWritable(Craft::$app->getPath()->getAppPath().'/requirements')) {
            throw new Exception(Markdown::process(Craft::t('app', 'Craft needs to be able to write to your craft/app/requirements folder and cannot. Please check your [permissions]({url}).', ['url' => 'http://buildwithcraft.com/docs/updating#one-click-updating'])));
        }

        $tempFilename = StringHelper::UUID().'.php';

        // Make a dupe of the requirements file and give it a random file name.
        Io::copyFile($requirementsFile, $requirementsFolderPath.'/'.$tempFilename);

        $newTempFilePath = Craft::$app->getPath()->getAppPath().'/requirements/'.$tempFilename;

        // Copy the random file name requirements to the requirements folder.
        // We don't want to execute any PHP from the storage folder.
        Io::copyFile($requirementsFolderPath.'/'.$tempFilename, $newTempFilePath);

        require_once(Craft::$app->getPath()->getAppPath().'/requirements/RequirementsChecker.php');

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
        Io::deleteFile($newTempFilePath);

        return $errors;
    }

    /**
     * Turns an array of messages into a Markdown-formatted bulleted list.
     *
     * @param string $messages
     *
     * @return string
     */
    private function _markdownList($messages)
    {
        $list = '';

        foreach ($messages as $message) {
            $list .= '- '.$message."\n";
        }

        return $list;
    }
}
