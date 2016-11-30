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
 * Class Zip
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Zip
{
    // Protected Methods
    // =========================================================================

    /**
     * @param $srcZip
     * @param $destFolder
     *
     * @return boolean
     */
    public static function unzip($srcZip, $destFolder)
    {
        Craft::$app->getConfig()->maxPowerCaptain();

        if (Io::fileExists($srcZip)) {
            if (Io::getExtension($srcZip) == 'zip') {
                if (!Io::folderExists($destFolder)) {
                    if (!Io::createFolder($destFolder)) {
                        Craft::error('Tried to create the unzip destination folder, but could not: '.$destFolder, __METHOD__);

                        return false;
                    }
                } else {
                    // If the destination folder exists and it has contents, clear them.
                    if (Io::getFolderContents($destFolder) !== false) {
                        // Begin the great purge.
                        if (!Io::clearFolder($destFolder)) {
                            Craft::error('Tried to clear the contents of the unzip destination folder, but could not: '.$destFolder, __METHOD__);

                            return false;
                        }
                    }
                }

                $zip = new ZipArchive();
                $result = $zip->unzip($srcZip, $destFolder);

                if ($result === true) {
                    return $result;
                }

                Craft::error('There was an error unzipping the file: '.$srcZip, __METHOD__);

                return false;
            }

            Craft::error($srcZip.' is not a zip file and cannot be unzipped.', __METHOD__);

            return false;
        }

        Craft::error('Unzipping is only available for files.', __METHOD__);

        return false;
    }
}
