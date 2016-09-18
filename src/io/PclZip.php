<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\io;

use Craft;
use craft\app\helpers\Io;

/**
 * Class PclZip
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PclZip implements ZipInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function zip($sourceFolder, $destZip)
    {
        $zip = new \PclZip($destZip);
        $result = $zip->create($sourceFolder, PCLZIP_OPT_REMOVE_PATH, $sourceFolder);

        if ($result == 0) {
            Craft::error('Unable to create zip file: '.$destZip, __METHOD__);

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function unzip($srcZip, $destFolder)
    {
        $zip = new \PclZip($srcZip);
        $tempDestFolders = null;

        // check to see if it's a valid archive.
        if (($zipFiles = $zip->extract(PCLZIP_OPT_EXTRACT_AS_STRING)) == false) {
            Craft::error('Tried to unzip '.$srcZip.', but PclZip thinks it is not a valid zip archive.', __METHOD__);

            return false;
        }

        if (count($zipFiles) == 0) {
            Craft::error($srcZip.' appears to be an empty zip archive.', __METHOD__);

            return false;
        }

        // find out which directories we need to create in the destination.
        foreach ($zipFiles as $zipFile) {
            if (substr($zipFile['filename'], 0, 9) === '__MACOSX/') {
                continue;
            }

            $folderName = Io::getFolderName($zipFile['filename']);
            if ($folderName == './') {
                $tempDestFolders[] = $destFolder.'/';
            } else {
                $tempDestFolders[] = $destFolder.'/'.rtrim(Io::getFolderName($zipFile['filename']),
                        '/');
            }
        }

        $tempDestFolders = array_unique($tempDestFolders);
        $finalDestFolders = [];

        foreach ($tempDestFolders as $tempDestFolder) {
            // Skip over the working directory
            if (rtrim($destFolder, '/') == rtrim($tempDestFolder, '/')) {
                continue;
            }

            // Make sure the current directory is within the working directory
            if (strpos($tempDestFolder, $destFolder) === false) {
                continue;
            }

            $finalDestFolders[] = $tempDestFolder;
        }

        asort($finalDestFolders);

        // Create the destination directories.
        foreach ($finalDestFolders as $finalDestFolder) {
            if (!Io::folderExists($finalDestFolder)) {
                if (!Io::createFolder($finalDestFolder)) {
                    Craft::error('Could not create folder '.$finalDestFolder.' while unzipping: '.$srcZip, __METHOD__);

                    return false;
                }
            }
        }

        unset($finalDestFolders);

        // Extract the files from the zip
        foreach ($zipFiles as $zipFile) {
            // folders have already been created.
            if ($zipFile['folder']) {
                continue;
            }

            if (substr($zipFile['filename'], 0, 9) === '__MACOSX/') {
                continue;
            }

            $destFile = $destFolder.'/'.$zipFile['filename'];

            if (!Io::writeToFile($destFile, $zipFile['content'], true, true)) {
                Craft::error('Could not copy the file '.$destFile.' while unziping: '.$srcZip, __METHOD__);

                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function add($sourceZip, $pathToAdd, $basePath, $pathPrefix = null)
    {
        $zip = new \PclZip($sourceZip);

        if (Io::fileExists($pathToAdd)) {
            $folderContents = [$pathToAdd];
        } else {
            $folderContents = Io::getFolderContents($pathToAdd, true);
        }

        $filesToAdd = [];

        if ($folderContents) {
            foreach ($folderContents as $itemToZip) {
                if (Io::isReadable($itemToZip)) {
                    if ((Io::folderExists($itemToZip) && Io::isFolderEmpty($itemToZip)) || Io::fileExists($itemToZip)) {
                        $filesToAdd[] = $itemToZip;
                    }
                }
            }
        }


        if (!$pathPrefix) {
            $pathPrefix = '';
        }

        $result = $zip->add($filesToAdd, PCLZIP_OPT_ADD_PATH, $pathPrefix, PCLZIP_OPT_REMOVE_PATH, $basePath);

        if ($result == 0) {
            Craft::error('Unable to add to zip file: '.$sourceZip, __METHOD__);

            return false;
        }

        return true;
    }
}
