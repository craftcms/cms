<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\io;

use Craft;
use craft\helpers\Io;

/**
 * Class ZipArchive
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ZipArchive
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function zip($sourceFolder, $destZip)
    {
        $zip = new \ZipArchive();

        $zipContents = $zip->open($destZip, \ZipArchive::CREATE);

        if ($zipContents !== true) {
            Craft::error('Unable to create zip file: '.$destZip, __METHOD__);

            return false;
        }

        return $this->add($destZip, $sourceFolder, $sourceFolder);
    }

    /**
     * @inheritdoc
     */
    public function unzip($srcZip, $destFolder)
    {
        Craft::$app->getConfig()->maxPowerCaptain();

        $zip = new \ZipArchive();
        $zipContents = $zip->open($srcZip, \ZipArchive::CHECKCONS);

        if ($zipContents !== true) {
            Craft::error('Could not open the zip file: '.$srcZip, __METHOD__);

            return false;
        }

        $success = $zip->extractTo($destFolder);
        $zip->close();

        if (!$success) {
            Craft::error('Could not extract the zip file at '.$srcZip.' to '.$destFolder, __METHOD__);

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function add($sourceZip, $pathToAdd, $basePath, $pathPrefix = null)
    {
        $zip = new \ZipArchive();
        $zipContents = $zip->open($sourceZip);

        if ($zipContents !== true) {
            Craft::error('Unable to open zip file: '.$sourceZip, __METHOD__);

            return false;
        }

        if (Io::fileExists($pathToAdd)) {
            $folderContents = [$pathToAdd];
        } else {
            $folderContents = Io::getFolderContents($pathToAdd, true);
        }

        if ($folderContents) {
            foreach ($folderContents as $itemToZip) {
                if (Io::isReadable($itemToZip)) {
                    // Figure out the relative path we'll be adding to the zip.
                    $relFilePath = mb_substr($itemToZip, mb_strlen($basePath));

                    if ($pathPrefix) {
                        $pathPrefix = Io::normalizePathSeparators($pathPrefix);
                        $relFilePath = $pathPrefix.'/'.$relFilePath;
                    }

                    if (Io::folderExists($itemToZip)) {
                        if (Io::isFolderEmpty($itemToZip)) {
                            $zip->addEmptyDir($relFilePath);
                        }
                    } else if (Io::fileExists($itemToZip)) {
                        // We can't use $zip->addFile() here but it's a terrible, horrible, POS method that's buggy on Windows.
                        $fileContents = Io::getFileContents($itemToZip);

                        if (!$zip->addFromString($relFilePath, $fileContents)) {
                            Craft::error('There was an error adding the file '.$itemToZip.' to the zip: '.$itemToZip, __METHOD__);
                        }
                    }
                }
            }
        }

        $zip->close();

        return true;
    }
}
