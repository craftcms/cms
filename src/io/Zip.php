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
     * @param $source
     * @param $destZip
     *
     * @return boolean 'true' if the zip was successfully created, 'false' if not.
     */
    public static function compress($source, $destZip)
    {
        $source = Io::normalizePathSeparators($source);
        $destZip = Io::normalizePathSeparators($destZip);

        if (!Io::folderExists($source) && !Io::fileExists($destZip)) {
            Craft::error('Tried to zip the contents of '.$source.' to '.$destZip.', but the source path does not exist.', __METHOD__);

            return false;
        }

        if (Io::fileExists($destZip)) {
            Io::deleteFile($destZip);
        }

        Io::createFile($destZip);

        Craft::$app->getConfig()->maxPowerCaptain();

        $zip = static::_getZipInstance();

        return $zip->zip(Io::getRealPath($source), Io::getRealPath($destZip));
    }

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
                    if (($conents = Io::getFolderContents($destFolder)) !== false) {
                        // Begin the great purge.
                        if (!Io::clearFolder($destFolder)) {
                            Craft::error('Tried to clear the contents of the unzip destination folder, but could not: '.$destFolder, __METHOD__);

                            return false;
                        }
                    }
                }

                $zip = static::_getZipInstance();
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

    /**
     * @param      $sourceZip
     * @param      $pathToAdd
     * @param      $basePath
     * @param null $pathPrefix
     *
     * @return boolean
     */
    public static function add($sourceZip, $pathToAdd, $basePath, $pathPrefix = null)
    {
        $sourceZip = Io::normalizePathSeparators($sourceZip);
        $pathToAdd = Io::normalizePathSeparators($pathToAdd);
        $basePath = Io::normalizePathSeparators($basePath);

        if (!Io::fileExists($sourceZip) || (!Io::fileExists($pathToAdd) && !Io::folderExists($pathToAdd))) {
            Craft::error('Tried to add '.$pathToAdd.' to the zip file '.$sourceZip.', but one of them does not exist.', __METHOD__);

            return false;
        }

        Craft::$app->getConfig()->maxPowerCaptain();

        $zip = static::_getZipInstance();

        if ($zip->add($sourceZip, $pathToAdd, $basePath, $pathPrefix)) {
            return true;
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * @return PclZip|ZipArchive
     */
    private static function _getZipInstance()
    {
        if (class_exists('ZipArchive')) {
            return new ZipArchive();
        }

        return new PclZip();
    }
}
